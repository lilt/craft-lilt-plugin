# craft-lilt-plugin Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 4.4.0 - 2023-10-04
### Added
- Queue manager

### Fixed 
- Download translations triggered only after all of them are done

## 4.3.0 - 2023-10-04
### Added
- Queue each translation file transfer separately

## 4.2.2 - 2023-09-22
### Fixed
- Empty user id on draft creation
- Nested blocks getting removed on current entry version when translations downloaded

## 4.2.1 - 2023-09-05
### Fixed
- Download diagnostic data functionality

## 4.2.0 - 2023-08-09
### Added
- Introduced new job and translation status "needs attention"
- Added warning message for translation jobs exceeding field limit with
- Included warning icon for each translation exceeding field limit with
- Download Diagnostic Data button for error reporting
- Notification if the plugin is outdated
- New queue job for manually syncing Lilt jobs
- Entry slug name added to translation filenames
- Priority for jobs from queue
- Background job for translation downloading
- Increase TTR for background jobs
- Option to enable target entries on translations publish
- Option to enable slug copy from source entries to target

### Changed
- Updated error message for failed jobs after retries
- Retry logic for queues
- Queues priority decreased for sending jobs to 1024 and receiving to 2048
- Deprecate FetchInstantJobTranslationsFromConnector and FetchVerifiedJobTranslationsFromConnector

### Fixed
- Merge canonical changes from the Neo field into drafts
- Added fallback for block elements without a created structure to fix content provider
- Resolved duplication issue with Neo and SuperTable fields
- Query for fetching translations by status and id
- Duplication of Neo fields content
- Fixed an issue with drafts being overridden due to an incorrect canonical merge
- Fixed a problem where translations were not updating to a failed status when a job fails
- Multilingual draft publishing issue
- Failed jobs aren't able to be deleted ([github issue](https://github.com/lilt/craft-lilt-plugin/issues/90))
- Copy source text for Matrix fields
- Configuration page issues
- Get versions for Jobs element
- Skip sync for new jobs
- Enabling of entries on translation publish
- Entry version content provider
- Updating of translation connector ids

### Fixed
- Multiple drafts apply issue for different sites
- Draft apply issue (all fields in translation draft now marked as changed for new drafts)

## 4.1.0 - 2022-10-11
### Added
- Retry logic for failed jobs

### Fixed
- Job and translation status update after sync action
- Error message for manual retry

## 4.0.0 - 2022-10-04
### Added
- Support of CraftCMS v4.0.0 and higher (^4.0.0)
