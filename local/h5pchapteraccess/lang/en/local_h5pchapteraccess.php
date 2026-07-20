<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * English language strings for local_h5pchapteraccess.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['accessmode'] = 'Access mode';
$string['accessmode_help'] = 'Available always shows the chapter. Locked always hides its content. Conditional uses '
    . 'Moodle availability restrictions configured for that chapter.';
$string['activechaptercount'] = 'Active chapters';
$string['activechapters'] = 'Active chapters';
$string['activityname'] = 'Activity';
$string['activitysettings'] = 'Activity settings';
$string['activitysummary'] = 'Activity and manifest';
$string['availabilityinvalid'] = 'This chapter is unavailable because its access conditions could not be evaluated.';
$string['availabilitydisabled'] = 'Conditional availability is disabled in this Moodle site.';
$string['availabilitypreservationnotice'] = 'These conditions are retained in all modes, but they are evaluated only '
    . 'when the mode is Conditional.';
$string['bridgeasset'] = 'Moodle communication bridge';
$string['bridgeassetmissing'] = 'Generated AMD file not found. Reinstall the complete plugin and purge caches.';
$string['bridgeassetpresent'] = 'Generated AMD file found';
$string['chaptercounts'] = 'Current chapter modes';
$string['chapteravailabilityname'] = 'H5P chapter: {$a}';
$string['chapterconfigurationsaved'] = 'Chapter access and availability conditions saved.';
$string['chapterpositionheading'] = 'Chapter {$a}';
$string['chaptersettings'] = 'Chapter settings';
$string['chaptertitle'] = 'Title';
$string['chapteruuid'] = 'Chapter ID';
$string['checkattention'] = 'Attention';
$string['checkerror'] = 'Required correction';
$string['checkok'] = 'OK';
$string['configurationsaved'] = 'Chapter access configuration saved.';
$string['conditionalwithoutconditions'] = 'Conditional mode has no configured conditions; the chapter remains available.';
$string['conditionaleditafter_save'] = 'Select Conditional and save to enable the Edit restrictions button for this chapter.';
$string['conditionalsettings'] = 'Moodle conditions';
$string['conditionconfigurationhint'] = 'On the next page you can select Conditional and configure its conditions in '
    . 'the same save. Stored conditions are ignored in the other modes.';
$string['configurationattentiontitle'] = 'Configuration needs attention.';
$string['configurationguide'] = 'How chapter access works';
$string['configurationguideintro'] = 'Enable the integration, choose a mode for each stable chapter, and save. Then open '
    . 'the activity with Moodle editing disabled to preview the student policy.';
$string['configurationneedsattention'] = 'Review the checks below. If communication is incomplete, the H5P safety '
    . 'fallback leaves every chapter available.';
$string['configurationready'] = 'The required artifacts are present, integration is enabled, and at least one chapter '
    . 'has an access rule.';
$string['configurationreadytitle'] = 'Ready to apply chapter access.';
$string['configurationstatus'] = 'Installation and configuration status';
$string['configureconditions'] = 'Configure conditional access';
$string['configuredrules'] = 'Blocked or conditional chapters';
$string['configuredrulesvalue'] = '{$a->restricted} of {$a->total} active chapters';
$string['conditionssummary'] = 'Configured restrictions';
$string['contenthash'] = 'Content hash';
$string['contentid'] = 'H5P content ID';
$string['coursemoduleid'] = 'Course module ID';
$string['currentmode'] = 'Saved mode';
$string['defaultmessage'] = 'Default locked message';
$string['defaultmessage_help'] = 'Plain-text message shown when a locked chapter has no specific message.';
$string['defaultlockedmessage'] = 'This chapter is currently unavailable.';
$string['deploymentnotice'] = 'Copying the plugin folder to another Moodle does not copy this activity\'s database '
    . 'rules and does not install the modified H5P library. On every Moodle site, install '
    . 'H5P.CustomizableInteractiveBook 1.0.32 or newer, run the Moodle plugin upgrade, purge caches, open this page, '
    . 'enable integration, and configure the chapters.';
$string['editchapterheading'] = 'Chapter {$a->position}: {$a->title}';
$string['editrestrictions'] = 'Edit restrictions';
$string['editrestrictionstitle'] = 'Edit chapter restrictions';
$string['error:accessdenied'] = 'You do not have access to H5P activity {$a}.';
$string['error:cmnotfound'] = 'Course module {$a} does not exist.';
$string['error:configurationmissing'] = 'The activity chapter configuration has not been synchronized.';
$string['error:contentmismatch'] = 'The requested H5P content does not belong to this activity.';
$string['error:duplicatechapterid'] = 'The H5P book contains the duplicate chapter ID "{$a}".';
$string['error:h5pnotfound'] = 'No deployed core H5P content was found for activity {$a}.';
$string['error:incompatiblelibrary'] = 'The H5P main library "{$a}" is not supported.';
$string['error:instancenotfound'] = 'The h5pactivity instance for course module {$a} does not exist.';
$string['error:invalidchapters'] = 'The H5P book has an invalid chapters configuration.';
$string['error:invalidjson'] = 'The H5P content parameters are not valid JSON.';
$string['error:chapternotcurrent'] = 'The requested chapter is not present in the current H5P manifest.';
$string['error:chapternotfound'] = 'The requested chapter does not belong to this activity configuration.';
$string['error:inactivechapter'] = 'Inactive chapters cannot be edited.';
$string['error:unstablechapter'] = 'A chapter without a stable subContentId cannot have persistent restrictions edited.';
$string['error:jsonunavailable'] = 'The file content/content.json could not be read from the H5P package.';
$string['error:packagenotfound'] = 'No H5P package was found for activity {$a}.';
$string['error:wrongmodule'] = 'The selected course module is "{$a}", not h5pactivity.';
$string['h5pchapteraccess:manage'] = 'Manage H5P chapter access';
$string['h5pchapteraccess:viewlocked'] = 'View locked H5P chapters while editing';
$string['inactivechapters'] = 'Inactive chapters';
$string['inactivechapterscaption'] = 'Chapters retained for diagnostic and future reactivation';
$string['inactivechaptersdescription'] = 'These records are absent from the current manifest. Their settings are retained '
    . 'and cannot be edited here.';
$string['integrationenabled'] = 'Enable chapter access integration';
$string['integrationenabled_help'] = 'When enabled, the configured chapter policy can be applied to the student view. '
    . 'Disabling it preserves all chapter settings.';
$string['integrationstatus'] = 'Integration';
$string['invalidaccessmode'] = 'Select Always available, Always locked, or Conditional.';
$string['invalidavailabilityconditions'] = 'The availability conditions are not valid.';
$string['manifesthash'] = 'Manifest hash';
$string['modelocked'] = 'Always locked';
$string['modelockeddescription'] = 'The real chapter content is not initialized and a plain-text explanation is shown.';
$string['modeconditional'] = 'Conditional';
$string['modeconditionaldescription'] = 'Moodle evaluates the configured date, group, grade, completion, or other '
    . 'availability conditions for each user.';
$string['modeopen'] = 'Always available';
$string['modeopendescription'] = 'The chapter is available to users who can access the activity.';
$string['modeunavailable'] = 'Not editable in this version';
$string['navigationtitle'] = 'H5P chapter access';
$string['noactivechapters'] = 'No active chapters were found in the current manifest.';
$string['norestrictionsconfigured'] = 'No restrictions configured.';
$string['pagetitle'] = 'H5P chapter access';
$string['pluginname'] = 'H5P chapter access';
$string['position'] = 'Position';
$string['openactivity'] = 'Open the H5P activity';
$string['privacy:metadata'] = 'The plugin stores access rules belonging to activities and chapters, not to individual users.';
$string['specificmessage'] = 'Specific locked message';
$string['specificmessage_help'] = 'Optional plain-text message for this chapter. Leave empty to use the activity default message.';
$string['showrestriction'] = 'Show the restriction explanation to the student';
$string['showrestriction_help'] = 'If enabled, Moodle condition information may be used after the specific message and '
    . 'before the activity default. The information is converted to plain text before it is sent to H5P.';
$string['stableid'] = 'Stable ID';
$string['stableidno'] = 'No';
$string['stableidyes'] = 'Yes';
$string['statusdisabled'] = 'Disabled';
$string['statusenabled'] = 'Enabled';
$string['studentviewnotice'] = 'These settings control chapter availability in the student view. A locked chapter '
    . 'remains packaged in the H5P file, but its child library is not initialized by the compatible Interactive Book '
    . 'library.';
$string['technicaldetails'] = 'Technical details';
$string['libraryruntime'] = 'Installed H5P library';
$string['libraryversionunknown'] = 'Version could not be determined';
$string['libraryversionvalue'] = '{$a->installed} (minimum with host contract: {$a->minimum})';
$string['restrictioneditorintro'] = 'The condition tree below is Moodle\'s standard Availability API editor. Available '
    . 'condition types depend on the plugins enabled for this site and course.';
$string['synchronizeagain'] = 'Synchronize manifest again';
$string['synchronizationsuccess'] = 'The chapter manifest was synchronized.';
$string['unstableidwarning'] = 'This chapter has no permanent subContentId. Its fallback ID depends on its current '
    . 'position, so persistent access configuration is disabled.';
$string['unstablecountnotice'] = '{$a} chapter(s) have no stable ID and cannot keep a persistent rule.';
$string['unsupportedmodewarning'] = 'This chapter uses a mode reserved for a future version and cannot be edited on this page.';
