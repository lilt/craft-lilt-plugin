# craft-lilt-plugin Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 3.4.2 - 2023-02-20
### Added 
- Timeouts for Connector API connection

## 3.4.1 - 2023-02-13
### Changed 
- Queues priority decreased for sending jobs to 1024 and receiving to 2048
- Deprecate FetchInstantJobTranslationsFromConnector and FetchVerifiedJobTranslationsFromConnector

### Fixed 
- Copy source text for Matrix fields

## 3.4.0 - 2023-02-06
### Added 
- Entry slug name added to translation filenames

## 3.3.0 - 2023-01-27
### Added 
- Priority for jobs from queue
- Background job for translation downloading

### Fixed
- Configuration page issues
- Get versions for Jobs element
- Skip sync for new jobs
- Enabling of entries on translation publish
- Entry version content provider 
- Updating of translation connector ids

## 3.2.1 - 2023-01-04
### Added 
- Increase TTR for background jobs 

## 3.2.0 - 2022-11-20
### Added 
- Option to enable target entries on translations publish
- Option to enable slug copy from source entries to target

## 3.1.2 - 2022-11-17
### Fixed 
- Multiple drafts apply issue for different sites 

## 3.1.1 - 2022-11-10
### Fixed 
- Draft apply issue (all fields in translation draft now marked as changed for new drafts)

## 3.1.0 - 2022-10-11
### Added 
- Retry logic for failed jobs

### Fixed 
- Job and translation status update after sync action
- Error message for manual retry

## 3.0.0 - 2022-10-04
### Added 
- Support of CraftCMS v3.0.0 and higher (^3.0.0)
### Fixed
- Style issue on create job page

## 0.8.1 - 2022-10-02
### Fixed
- Status query for translation elements index
- Translation elements capability with different versions of CraftCMS
- Invalidate cache for translations on publish & review

## 0.8.0 - 2022-09-08
### Added
- Asynchronous job transfer to lilt platform

## 0.7.2 - 2022-09-08
### Added
- Spinner for not loaded element index on translation preview page

### Fixed
- Joins for translations search query

## 0.7.1 - 2022-09-08
### Fixed
- Column name mask for translation query

## 0.7.0 - 2022-09-08
### Added
- Added new button to publish changes without review

### Fixed
- Element index behaviour 
- New job status color
- Custom sources for element index modal

## 0.6.0 - 2022-09-04
### Added
- New translations element index on the preview page

## 0.5.1 - 2022-08-27
### Fixed
- Translation preview page: modal not opening issue 

## 0.5.0 - 2022-08-27
### Added
- Default sorting for translations
 
## 0.4.3 - 2022-08-17
### Fixed
- Removed value updating for BaseOption & Lightswitch fields

## 0.4.2 - 2022-08-17
### Fixed
- Translation applier wasn't able to find source element for site id  
- Wrong language formatting when locale is empty

## 0.4.1 - 2022-08-16
### Added
- Error logging for all exception catches  

## 0.4.0 - 2022-08-13
### Added
- Remove entry translation drafts on job removal
- Empty content now excluded from translation source body
- Warning for job entries, when translation in progress (on job creation) 
- Fixed: Empty I18N entry creation
- Fixed: Translation preview issue (error while encoding of json)
- Fixed: Hide warning when element index not exist
- Fixed: Argument 2 passed to craft\services\Drafts::createDraft() must be of the type int, null given

## 0.3.1 - 2022-08-13
### Added
- Updated connector sdk dependency 

## 0.3.0 - 2022-08-03
### Added
- Fixed applying translations for nested fields 

## 0.2.1 - 2022-08-03
### Added
- Initial beta release
