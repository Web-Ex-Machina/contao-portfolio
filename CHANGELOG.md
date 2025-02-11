
# Extension "Portfolio" for Contao Open Source CMS

## 3.3 - 2025-02-XX
- ADDED - New setting for filters to hide filters and options with no results

## 3.2 - 2025-02-05
- ADDED - Slug for translations + URL adjustements
- ADDED - ChangeLanguage listener to redirect properly to the translation url
- ADDED - Sorting settings for list module
- UPDATED - Backend - language marked in items list and items contents
- UPDATED - Backend - use the rights icons to edit portfolio feed or access feed items
- UPDATED - Frontend - List item restricts config to the current language
- UPDATED - Fix translations (French and English)

## 3.1.1 - 2025-01-31
- UPDATED - Fixes & Backend improvements

## 3.1 - 2024-12-19
- ADDED - Add a new textarea attribute that allows you to use a tinyMCE or a HTML field as attribute
- ADDED - Add a translation subtable for attributes
- ADDED - Allow frontend module lists to be preconfigured
- ADDED - Add feed settings to retrieve data from a remote source
- ADDED - API route can now count items
- UPDATED - API can now filter items by lang
- UPDATED - API retrieve more data for portfolio items and their attributes
- UPDATED - URLs of portfolio items now include their category so we can know if it's a local/remote feed
- UPDATED - We can now specify a language when retrieving an item attributes
- UPDATED - Fix some typos here and there
- UPDATED - Few fixes on multiple filters
- UPDATED - Remove useless dependencies
- UPDATED - Do not allow dev dependencies anymore
- bundle `webexmachina/contao-utils` upgraded to `^2.0`

## 3.0 - 2024-08-26
- Complete refactor of attribute logic
- bundle `webexmachina/contao-utils` upgraded to `^1.0`
- ADDED - Add a new tag "countoffers", returns the number of published portfolio in one or several PIDs
- ADDED - Add a new tag "portfolio", return a value for the current portfolio or for a specific ID
- ADDED - Add a frontend module to display a portfolio directly
- UPDATED - Move the filters into a dedicated frontend module

## 2.1 - 2020-04-20
- Add custom sorting system for categories
- Improve categories listing

## 2.0 - 2020-04-18
- Add categories
- Add categories listing
- Improve attributes system
- Improve items listing

## 1.3 - 2020-03-25
- Update structure in SymfonyBundle
- Update Changelog & Readme

## 1.2 - 2020-02-06
- English translation

## 1.1 - 2019-05-09
- Syntax update (phpcsfixer)
- Fixes

## 1.0 - 2018-01-14
- Handle main porfolio features (list, reader...)
- Create and attach tags to portfolio items
- Create attributes and fill values for each items
- Use the Contao pages as Portfolio categories
- Attach multiple files to portfolio items and display them in your templates

## 0.2 - 2018-01-06
- Add generic classes for flexibility purposes

## 0.1 - 2017-11-03
- Init git repository
- Composer tests
- Add severals fields to extends configuration
- Start the frontend modules
