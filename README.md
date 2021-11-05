# Sitegeist.LostInTranslation.CsvPO
## Fill gaps in CsvPO translations by using the Deepl Service from Sitegeist.LostInTranslation.


### Authors & Sponsors

* Martin Ficzel - ficzel@sitegeist.de

*The development and the public-releases of this package is generously sponsored
by our employer http://www.sitegeist.de.*

## Installation

Sitegeist.LostInTranslation is available via packagist. Run `composer require sitegeist/lostintranslation-csvpo`.

We use semantic-versioning so every breaking change will increase the major-version number.

## Usage 

The package finds missing translations, translates them via DeepL API and creates translation overrides in the database.
Those can then be tested and later be baked back to the csv files via commands of the CsvPO package. 

### General workflow
1. Use the command `./flow csvpo:translateAll` or  `./flow csvpo:translate` to create new translation definitions.
2. Test the new translations !!!
3. Safe the translations to the csv via `./flow csvpo:bakeAll` or `./flow csvpo:bake` to update the translation csv files. 
4. Reset then translation overrides again `./flow csvpo:resetAll` or `./flow csvpo:reset`

### Examples of translation commands

1. Add missing french translations to all sources from german.
```
./flow csvpo:translateAll de fr
```
 
2. Add missing danisch translations to all sources from german but specify deeply locale.
```
./flow csvpo:translateAll de dk --deeplTarget da
```

3. Calculate all french translations from german again regardless wether they already exist.
```
./flow csvpo:translate resource://Vendor.Site/Private/Example.csv de fr --force
```

### Commands 

1. CsvPO:TranslateAll:  Add missing translations for all translation sources

```
./flow csvpo:translateall [<options>] <source> <target>

ARGUMENTS:
  --source             Locale identifier of the source language
  --target             Locale identifier of the target language

OPTIONS:
  --force              Force translation of all labels
  --deepl-source       Source language identifier for DeepL, falls back to $source if not defined
  --deepl-target       Target language identifier for DeepL, falls back to $target if not defined
```

2. CsvPO:Translate: Add missing translations for the given translation source
 
```
./flow csvpo:translate [<options>] <identifier> <source> <target>

ARGUMENTS:
  --identifier         The translation source identifier (aka the
                       resource://filename of the csv file)
  --source             Locale identifier of the source language
  --target             Locale identifier of the target language

OPTIONS:
  --force              Force translation of all labels
  --deepl-source       Source language identifier for DeepL, falls back to $source if not defined
  --deepl-target       Target language identifier for DeepL, falls back to $target if not defined
```



## Workflow 


## Contribution

We will gladly accept contributions. Please send us pull requests.
