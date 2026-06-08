<?php

/**
 * webtrees module: dead-record-detective
 * Scans a Webtrees family tree and detects individuals with missing,
 * unknown, or placeholder data in key fields, and generates a detailed
 * report for the administrator to review and correct manually.
 *
 * "We find them. YOU kill them. We detect them. YOU reject them!"
 *
 * Copyright (C) 2026 Bill Kochman.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details:
 * <https://www.gnu.org/licenses/>
 */

declare(strict_types=1);

namespace BillKochman\WtModule\DeadRecordDetective;

use Fisharebest\Webtrees\I18N;
use Fisharebest\Webtrees\Tree;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Module\AbstractModule;
use Fisharebest\Webtrees\Module\ModuleConfigInterface;
use Fisharebest\Webtrees\Module\ModuleConfigTrait;
use Fisharebest\Webtrees\Module\ModuleCustomInterface;
use Fisharebest\Webtrees\Module\ModuleCustomTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DeadRecordDetective extends AbstractModule implements ModuleCustomInterface, ModuleConfigInterface
{
    use ModuleCustomTrait;
    use ModuleConfigTrait;

    // Module constants
    public const CUSTOM_MODULE       = 'dead-record-detective';
    public const CUSTOM_AUTHOR       = 'Bill Kochman';
    public const CUSTOM_WEBSITE      = 'https://github.com/0ldM4cM4n/dead-record-detective';
    public const CUSTOM_VERSION      = '1.0.0';
    public const CUSTOM_LAST_VERSION = 'https://raw.githubusercontent.com/0ldM4cM4n/dead-record-detective/main/module.php';
    public const CUSTOM_SUPPORT_URL  = 'https://github.com/0ldM4cM4n/dead-record-detective/issues';

    // Maximum age threshold — anyone born more than this many years ago is treated as deceased
    private const MAX_LIVING_AGE = 120;

    /**
     * {@inheritDoc}
     */
    public function title(): string
    {
        return I18N::translate('Dead Record Detective');
    }

    /**
     * {@inheritDoc}
     */
    public function description(): string
    {
        return I18N::translate('Scans a family tree and detects individuals with missing, unknown, or placeholder data in key fields.');
    }

    /**
     * {@inheritDoc}
     */
    public function customModuleAuthorName(): string
    {
        return self::CUSTOM_AUTHOR;
    }

    /**
     * {@inheritDoc}
     */
    public function customModuleVersion(): string
    {
        return self::CUSTOM_VERSION;
    }

    /**
     * {@inheritDoc}
     */
    public function customModuleLatestVersionUrl(): string
    {
        return self::CUSTOM_LAST_VERSION;
    }

    /**
     * {@inheritDoc}
     */
    public function customModuleSupportUrl(): string
    {
        return self::CUSTOM_SUPPORT_URL;
    }

    /**
     * Bootstrap the module
     */
    public function boot(): void
    {
        \Fisharebest\Webtrees\View::registerNamespace($this->name(), $this->resourcesFolder() . 'views/');
    }

    /**
     * Where does this module store its resources?
     */
    public function resourcesFolder(): string
    {
        return __DIR__ . '/resources/';
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function getAdminAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->layout = 'layouts/administration';

        $tree_service = \Fisharebest\Webtrees\Registry::container()->get(\Fisharebest\Webtrees\Services\TreeService::class);
        $tree_list    = [];

        foreach ($tree_service->all() as $tree) {
            $tree_list[$tree->id()] = $tree->title();
        }

        return $this->viewResponse($this->name() . '::admin', [
            'title'       => $this->title(),
            'module_name' => $this->name(),
            'tree_list'   => $tree_list,
        ]);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function postAdminAction(ServerRequestInterface $request): ResponseInterface
    {
        $params      = (array) $request->getParsedBody();
        $tree_id     = (int) ($params['tree_id'] ?? 0);
        $scan_for    = (array) ($params['scan_for'] ?? []);
        $export_type = $params['export_type'] ?? 'tsv';

        $tree_service = \Fisharebest\Webtrees\Registry::container()->get(\Fisharebest\Webtrees\Services\TreeService::class);
        $tree         = $tree_service->find($tree_id);

        if ($tree === null) {
            return redirect(route('module', [
                'module' => $this->name(),
                'action' => 'Admin',
            ]));
        }

        $rows = \Fisharebest\Webtrees\DB::table('individuals')
            ->where('i_file', '=', $tree->id())
            ->get();

        $problems     = [];
        $current_year = (int) date('Y');

        foreach ($rows as $row) {
            $individual = \Fisharebest\Webtrees\Registry::individualFactory()->make($row->i_id, $tree);

            if ($individual === null || !$individual->canShow(Auth::PRIV_NONE)) {
                continue;
            }

            $full_name  = $individual->getAllNames()[0] ?? [];
            $first_name = ($full_name['givn'] ?? '');
            $last_name  = ($full_name['surn'] ?? '');

            $birth_date = '';
            $death_date = '';

            $birth = $individual->getBirthDate();
            if ($birth->isOK()) {
                $birth_date = strip_tags($birth->display());
            }

            $death = $individual->getDeathDate();
            if ($death->isOK()) {
                $death_date = strip_tags($death->display());
            }

            // Determine if individual is truly still living:
            // - isDead() must return false (Webtrees living flag), AND
            // - Birth year must be within the last MAX_LIVING_AGE years (or unknown)
            $birth_year  = $birth->isOK() ? $birth->minimumDate()->year : 0;
            $is_too_old  = ($birth_year > 0 && ($current_year - $birth_year) > self::MAX_LIVING_AGE);
            $is_living   = !$individual->isDead() && $birth_year > 0 && !$is_too_old;

            $individual_problems = [];

            // Check for unknown or empty last name (@N.N.)
            if (in_array('unknown_last_name', $scan_for)) {
                if ($last_name === '@N.N.') {
                    $individual_problems[] = 'No Last Name';
                }
            }

            // Check for unknown or empty first name (? or @P.N.)
            if (in_array('unknown_first_name', $scan_for)) {
                if ($first_name === '?' || $first_name === '@P.N.' || trim($first_name) === '') {
                    $individual_problems[] = 'No First Name';
                }
            }

            // Check for empty birth date
            if (in_array('empty_birth_date', $scan_for)) {
                if ($birth_date === '') {
                    $individual_problems[] = 'BirthDate?';
                }
            }

            // Check for empty death date — skip if individual is truly still living
            if (in_array('empty_death_date', $scan_for)) {
                if ($death_date === '' && !$is_living) {
                    $individual_problems[] = 'DeathDate?';
                }
            }

            // Replace display values AFTER scanning checks
            $first_name = ($first_name === '' || $first_name === '?' || $first_name === '@P.N.') ? '[No Name]' : $first_name;
            $last_name  = ($last_name === '' || $last_name === '@N.N.') ? '[No Name]' : $last_name;
            $birth_date = ($birth_date === '') ? '[BirthDate?]' : $birth_date;
            $death_date = ($death_date === '') ? ($is_living ? '[Still Living]' : '[DeathDate?]') : $death_date;

            if (!empty($individual_problems)) {
                $problems[] = [
                    'id'         => $row->i_id,
                    'first_name' => $first_name,
                    'last_name'  => $last_name,
                    'birth_date' => $birth_date,
                    'death_date' => $death_date,
                    'problems'   => $individual_problems,
                    'url'        => $individual->url(),
                ];
            }
        }

        usort($problems, function ($a, $b) {
            $last = strcasecmp($a['last_name'], $b['last_name']);
            return $last !== 0 ? $last : strcasecmp($a['first_name'], $b['first_name']);
        });

        switch ($export_type) {
            case 'csv':
                return $this->exportCsv($problems);
            case 'print':
                return $this->exportPrint($problems, $tree, $scan_for);
            case 'tsv':
            default:
                return $this->exportTsv($problems);
        }
    }

    /**
     * Export as TSV for BBEdit
     */
    private function exportTsv(array $problems): ResponseInterface
    {
        $col1 = strlen('Last Name');
        $col2 = strlen('First Name');
        $col3 = strlen('Birth Date');
        $col4 = strlen('Death Date');

        foreach ($problems as $problem) {
            $col1 = max($col1, strlen($problem['last_name']));
            $col2 = max($col2, strlen($problem['first_name']));
            $col3 = max($col3, strlen($problem['birth_date']));
            $col4 = max($col4, strlen($problem['death_date']));
        }

        $output = sprintf("%-{$col1}s\t%-{$col2}s\t%-{$col3}s\t%-{$col4}s\t%s\n\n",
            'LAST NAME', 'FIRST NAME', 'BIRTH DATE', 'DEATH DATE', 'ID');

        foreach ($problems as $problem) {
            $output .= sprintf("%-{$col1}s\t%-{$col2}s\t%-{$col3}s\t%-{$col4}s\t%s\n",
                $problem['last_name'],
                $problem['first_name'],
                $problem['birth_date'],
                $problem['death_date'],
                $problem['id']);
        }

        return response($output)
            ->withHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->withHeader('Content-Disposition', 'attachment; filename="dead-record-detective.tsv"');
    }

    /**
     * Export as CSV for Excel/Numbers
     */
    private function exportCsv(array $problems): ResponseInterface
    {
        $output = "Last Name,First Name,Birth Date,Death Date,ID\n";

        foreach ($problems as $problem) {
            $output .= implode(",", [
                '"' . str_replace('"', '""', $problem['last_name']) . '"',
                '"' . str_replace('"', '""', $problem['first_name']) . '"',
                '"' . str_replace('"', '""', $problem['birth_date']) . '"',
                '"' . str_replace('"', '""', $problem['death_date']) . '"',
                '"' . str_replace('"', '""', $problem['id']) . '"',
            ]) . "\n";
        }

        return response($output)
            ->withHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->withHeader('Content-Disposition', 'attachment; filename="dead-record-detective.csv"');
    }

    /**
     * Export as Print View
     */
    private function exportPrint(array $problems, Tree $tree, array $scan_for): ResponseInterface
    {
        $scan_labels = [
            'unknown_last_name'  => I18N::translate('Unknown or empty last name (@N.N.)'),
            'unknown_first_name' => I18N::translate('Unknown or empty first name (? or @P.N.)'),
            'empty_birth_date'   => I18N::translate('Empty birth date field'),
            'empty_death_date'   => I18N::translate('Empty death date field'),
        ];

        $enabled_options = [];
        foreach ($scan_for as $key) {
            if (isset($scan_labels[$key])) {
                $enabled_options[] = $scan_labels[$key];
            }
        }

        return $this->viewResponse($this->name() . '::print', [
            'title'           => I18N::translate('Dead Record Detective'),
            'tree'            => $tree,
            'problems'        => $problems,
            'enabled_options' => $enabled_options,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function customModuleLatestVersion(): string
    {
        return '';
    }

} // End of DeadRecordDetective class
