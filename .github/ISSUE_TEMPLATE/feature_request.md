**Title:** `[FEATURE REQUEST] `

**Describe the feature**
A clear and concise description of the functionality you would like to see in `nepali-date-library`.
For example: support for converting times, adding custom calendars, or handling edge-case dates.

**Motivation / Use Case**
Explain why this feature would be useful and how you plan to use it.
Example:

- “I want to convert Gregorian dates with time to Nepali dates for a calendar app.”
- “I need support for Nepali fiscal year calculations in my accounting software.”

**Proposed Solution**
(Optional) Describe how you think this feature could be implemented or integrated.
Example:

```php
use NepaliDateLibrary\NepaliDate;

$nd = new NepaliDate("2026-03-16");
$result = $nd->format("YYYY-MM-DD");
echo $result;
// Expected output: "2079-12-02"
```

**Alternatives Considered**
If you’ve tried workarounds or other libraries, mention them.
Example:

- Using manual mapping of dates
- Using another library but it lacks accuracy

**Additional Context**
Any other details, mockups, links, or examples that would help the maintainer understand your request.

**Environment (Optional, if relevant)**

- PHP Version: [e.g. 8.3]
- `nepali-date-library` Version: [e.g. 1.2.0]
- OS: [e.g. Ubuntu 22.04, macOS 13]
