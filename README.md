# Gettext Miner

**Gettext Miner** is a tool for extracting translatable strings from PHP, Latte, Neon, and other files.

The tool comes bundled with extractors for these formats:
* **PHP** using calls like `$t->translate('full string')` or `$t->_('full string')` or with pluralization `$t->translate('Your basket has %d items!', $count)`.
* **Latte** templates in multiple variants: `{_'full string'}` and `$t->translate('full string')`.
* **Neon** config files with configurable paths to extract.

More over it has comes with extractor tuned for **Nette** applications and **Nette** forms which can extract strings from functions internally using translator, such as `$form->addText('fullname', 'Your full name')`.

The extracted strings can be persisted into a gettext template file or a PHP file with an PHP array.

Also, the support for multiple build targets (aka modules) within one project is present.

## Configuration

Before usage, you need to configure the tool in project root file named `.gettext-miner.neon`, see example config below.

```neon
# Define particular target
Module1:
    # All directories that should be scanned recursively
    sources:
        - app/Modules/Module1/
        - app/Config/
    # Optional explicit list of files to parse
    files:                     
        - extras/foo.php
    # Extractors configuration (keys are just random identifiers)
    extractors:
        php:
            extractor: PHP
            # Append additional functions and argument position to be extracted
            extraFunctions:
                myTranslate: 1
            # Optional: Replace default configuration with completely custom set of functions
            # functions:
            #    myTranslate: 1
            # Optional: overwrite default extensions
            # extensions:
            # - php
        latte:
            extractor: Latte
            # Optional: Additional modifiers (Latte filters) names or regexes
            extraModifiers:
                - myFilter #ensure that the provided content is properly quoted for usage in preg functions
                - myTruncate[^|]+               # allow filter with parameters up to next filter or to end of Latte tag
                - myTruncate[^|]+\|noescape     # multiple composed filters has to be explicitly allowed
            # Optional: ovewrite default extensions
            # extensions:
            # - latte
        sql:
            extractor: SQL      # wrap string like this for extraction /*_*/'String'/*_*/ 
            # Optional: ovewrite default extensions
            # extensions:
            # - sql
        neon:
            extractor: Neon
            paths:
                app/config/config.neon: # we need to specify neon file path
                    - parameters|myArray # and path to extract strings
            # Optional: ovewrite default extensions
            # extensions:
            # - neon
    # Configure output
    output:
        # Path where to store the gettext template
        destination: app/Modules/Module1/Locales/template.pot
        formatter: Gettext
```

Supported extractors:
* `PHP` alias of `DrabekDigital\GettextMiner\Extractors\PHP`
* `LegacyLatte` alias of `DrabekDigital\GettextMiner\Extractors\LegacyLatte`
* `Neon` alias of `DrabekDigital\GettextMiner\Extractors\Neon`
* `SQL` alias of `DrabekDigital\GettextMiner\Extractors\SQL`
* `Nette` alias of `DrabekDigital\GettextMiner\Extractors\Nette`

If you wish to implement custom extractor you can do it by implementing `DrabekDigital\GettextMiner\Extractors\Extractor` interface.

Supported output types:
* `Gettext` alias of `DrabekDigital\GettextMiner\OutputFormatters\Gettext`
* `ArrayFile` alias of `DrabekDigital\GettextMiner\OutputFormatters\ArrayFile`

If you wish to implement custom output format you can do it by implementing `DrabekDigital\GettextMiner\OutputFormatters\OutputFormatter` interface.

## Usage

1. Either install the tool globally or add it to your project dependencies.

```bash
# Global installation
$ composer global require drabek-digital/gettext-miner

# Project installation
$ composer require --dev drabek-digital/gettext-miner
```

2. Configure the tool in the root of your project in `.gettext-miner.neon`, see section above

3. Run the tool with the following command:

```bash
$ gettext-miner extract project-dir/
```
4. Profit!

## Output formatters

### Gettext

Use this formatter when you want to generate a gettext template file to be translated
in stardard gettext tools, such as [Poedit](https://poedit.net/).

```neon
    # ...
    output:
        destination: template.pot
        formatter: Gettext
        extraMeta:
            key: value
        detector: SymfonyTranslationPluralDetector
        lineNumbers: true
    # ...
```

Will output this:

```
# Created: 2025-01-03T11:19:36+02:00

msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\n"
"Plural-Forms: nplurals=2; plural=(n != 1);\n"
"key: value\n"

```

### ArrayFile

Use this formatter when you want to generate a PHP file with an array of strings.

```
    # ...
    output:
        destination: strings.php
        formatter: ArrayFile
        indent: tabs
        outputVariable: translations
    # ...
```

Will output this:

```
<?php declare(strict_types = 1);
$translations = [
	'Translated string',
];
```

## Filters usage guidance

The implementation of each filter is up to the extractor. Some filters like PHP are using PHP tokenization, while
others like Latte are using regular expressions, which can cause issues with complex and non-standard/simple syntax.

When you find yourself in a situation when in PHP code you need to extract some random string outside a translation function call prepend
`/*gettext-miner*/` comment just before the string, like `const FOO = /*gettext-miner*/ 'enum value label';`

Also, it is worth mentioning that in all code ensure that functions are provided with simple non-concatenated strings, like `$t->translate('Hello world')` and not `$t->translate('Hello' . ' world')`.

Beware that Latte filter only use a reasonable subset of syntax, consult the code to stay on the safe side.

## Pluralization detection

Gettext format supports pluralization, however the template needs to be already written
with plural forms defined. The tool can detect plural forms in PHP code using the
plural detector.

It has to be specified in the configuration:

```neon
    # ...
    output:
        destination: template.pot
        formatter: Gettext
        detector: SymfonyTranslationPluralDetector
    # ...
```

Supported detectors:
* `SprintfPluralDetector` alias of `DrabekDigital\GettextMiner\Detectors\SprintfPluralDetector`, which will detect plural form if `%d` is present.
* `SymfonyTranslationPluralDetector` alias of `DrabekDigital\GettextMiner\Detectors\SymfonyTranslationPluralDetector`, which will detect plural form if `%count%` is present.

## Other notes

All paths are relative to the project root.

Be careful with paths within the project as for example on Linux it is possible to create directory or file named `a\b` which however
converts into Gettext path reference as `a/b` which has different meaning and won't work for reference click through.
Sadly this cannot be easily detected and fixed.

