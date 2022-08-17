# craft-lilt-plugin Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 0.4.2 - 2022-08-03
### Fixed
- Translation applier wasn't able to find source element for site id  
- Wrong language formatting when locale is empty

## 0.4.1 - 2022-08-03
### Added
- Error logging for all exception catches  

## 0.4.0 - 2022-08-03
### Added
- Remove entry translation drafts on job removal
- Empty content now excluded from translation source body
- Warning for job entries, when translation in progress (on job creation) 
- Fixed: Empty I18N entry creation
- Fixed: Translation preview issue (error while encoding of json)
- Fixed: Hide warning when element index not exist
- Fixed: Argument 2 passed to craft\services\Drafts::createDraft() must be of the type int, null given

## 0.3.1 - 2022-08-03
### Added
- Updated connector sdk dependency 

## 0.3.0 - 2022-08-03
### Added
- Fixed applying translations for nested fields 

## 0.2.1 - 2022-08-03
### Added
- Initial beta release
