![B&C Mods](https://raw.githubusercontent.com/0ldM4cM4n/dead-record-detective/main/assets/images/B-C-Mods-Logo-02-optimized.png)

# dead-record-detective
**Version 1.0.0**

"We find them. YOU kill them. We detect them. YOU reject them!"

Based on the options set by the admin, this tool scans a Webtrees family tree and detects individuals with missing, unknown, or placeholder data in key fields — specifically first and last names, and birth and death dates. It doesn't modify, delete, or alter any family tree data. All changes must be made manually by the admin. Results can be exported as a text file or spreadsheet, or viewed inline inside your Webtrees site.

## Where To Find It
Dead Record Detective is found on the main **Control Panel** page, under the **Modules** section, in the **Other** subsection on the right side of the browser window.

## Features
- ✅ Four scan options covering unknown/empty names and missing date fields
- ✅ Three export formats: TSV, CSV, and Print View
- ✅ Fixed-width column alignment in text exports
- ✅ Unknown or empty names clearly labeled in purple
- ✅ Still-living individuals intelligently detected and labeled in blue
- ✅ Enabled scan options displayed at the top of every Print View report
- ✅ Clickable profile ID links in Print View for quick navigation
- ✅ No core Webtrees files modified — update safe!

## Compatibility

### webtrees 2.2
This module is developed and tested with webtrees 2.2.6 running under PHP 8.4.

### webtrees 2.1
This module should work with webtrees 2.1.x but has not been explicitly tested.

### webtrees 2.0 and lower
This module **will not work** with webtrees versions lower than 2.1.

## Installation Instructions
1. Download the zip archive of this repository from GitHub.
2. Unzip the archive.
3. Place the entire `dead-record-detective` folder into the `modules_v4` directory on your server.
4. That's it! Webtrees will automatically detect and load the module.

## Usage
1. Go to the **Control Panel**.
2. Find **Dead Record Detective** in the **Modules** section under **Other** on the right side of the page, and click the **wrench** icon next to it.
3. Select a family tree from the dropdown.
4. Choose one or more scan options (see **Scan Options** below).
5. Choose an export format (see **Export Formats** below).
6. Click **Generate Report**.

## Scan Options

### Unknown Or Empty Last Name (@N.N.)
Scans for individuals whose last name field contains the GEDCOM placeholder code @N.N., or whose last name field has been left blank. @N.N. is the standard genealogy code used to indicate that a surname is unknown or missing. Webtrees automatically assigns @N.N. to any record where the surname field is left empty, so both conditions are detected by this single option.

### Unknown Or Empty First Name (? or @P.N.)
Scans for individuals whose first name field contains the GEDCOM placeholder codes ? or @P.N., or whose first name field has been left blank. Both ? and @P.N. are standard genealogy codes used to indicate that a given name is unknown or missing.

### Empty Birth Date Field
Scans for individuals whose birth date field contains no date information of any kind.

### Empty Death Date Field
Scans for individuals whose death date field contains no date information of any kind. Individuals who are determined to be still living are automatically excluded from this scan and will instead display **[Still Living]** in the Death Date column if they appear in the results for another reason.

## How Living Individuals Are Detected
Dead Record Detective uses the following logic to determine whether an individual is still living:

- The individual must be flagged as living by Webtrees, AND
- The individual must have a known birth year that falls within the last 120 years.

If an individual has no birth year on record, they cannot be confirmed as living and will be treated as deceased for reporting purposes. This prevents historical records with no date data from being incorrectly labeled as living.

## Export Formats

### TSV (Tab Separated Values)
Produces a plain text file with fixed-width padded columns for clean alignment in any text editor such as BBEdit. The file can also be opened directly in Apple Numbers or Microsoft Excel, where it will display in neat, properly aligned columns.

### CSV (Excel / Numbers)
Produces a standard comma-separated values file optimized for use in spreadsheet applications such as Apple Numbers or Microsoft Excel.

### Print View
Displays the results as a formatted, printer-friendly HTML page within your Webtrees browser window. The Print View includes:
- The tree name and report generation date
- A list of the scan options enabled for that report
- The total number of problems found
- An important disclaimer
- Usage instructions
- A color-coded results table

It does not send anything directly to a printer. If you wish to produce a physical printed copy, use your web browser's built-in print function (File → Print, or Cmd+P / Ctrl+P) once the Print View is displayed on screen. A printer must be connected to your device to print a hard copy.

## Notes On Color Coding In Print View
- **[Purple brackets]** indicate a missing or incomplete data field — this is a problem that needs attention.
- **[Blue brackets]** are informational only and do not indicate a problem. For example, **[Still Living]** appears in blue to indicate that the individual is known to be alive and the absence of a death date is expected.

## Important Disclaimer
This module is a detection and reporting tool ONLY. It does not modify, delete, or alter any data in your family tree. All changes must be made manually by the administrator. B&C Mods accepts no responsibility for any actions taken by the admin — or consequences resulting from the same — based on the results of this report. Use this tool at your own discretion.

## Known Limitations
- Due to the wide variety of data entry styles used by different users, some exported output may not always be perfectly aligned in text files.
- Solo four-digit years in CSV exports may appear right-aligned in spreadsheet applications, as they are interpreted as numbers rather than text.

## Upgrade Safety
This module operates entirely within the Webtrees module system. No core Webtrees files are modified.

## Privacy, Telemetry, Tracking
Privacy: yes. Tracking: no.

The module will check for the latest available version whenever the Webtrees Control Panel is opened. It checks a URL on github.com only.

## Credits
Developed by Bill Kochman with assistance from Claude (Anthropic).

## License
Copyright (C) 2026 Bill Kochman.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

## Warranty
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details:
https://www.gnu.org/licenses/
