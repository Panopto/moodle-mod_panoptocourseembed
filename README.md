## Moodle Panopto Course Embed Tool
This plugin adds a Panopto Course Embed tool to Moodle. This tool can be used to embed Panopto content directly into a Moodle course.

## Documentation
For the most up to date documentation for the Panopto Course Embed plugin please see our [online documentation](https://support.panopto.com).

## Installation
1. Download the Moodle Panopto Course Embed zip file from the [github repository](https://github.com/Panopto/moodle-mod_panoptocourseembed/releases). We will work with Moodle.org to add this into the app directory. Until then please use our github as the official release site.
2. Navigate to the target Moodle site and log in as an administrator
3. Navigate to ```Site Administration -> Plugins -> Install Plugins```
4. Drag the zip file into the drag & drop box and go through the installation process.
5. An LTI Tool for the Panopto server must be configured on the Moodle site.
6. Please navigate to ```Site administration -> Plugins -> Activity modules -> External tool -> Manage preconfigured tools```
7. Click ```Add Preconfigured tool```
8. Input the following information

    -   For LTI 1.1:
        -   Tool Name: ```[panoptoServer] Course Embed Tool```
        -   Tool Url: ```https://[panoptoServer]/Panopto/LTI/LTI.aspx```
        -   Consumer Key: ```[Identity Provider > Instance Name]```
        -   Shared secret: ```[Identity Provided > Application Key]```
        -   Custom Parameters:
                ```
                panopto_course_embed_tool=true
                ```
    -   For LTI 1.3:
        -   Tool Name: ```[panoptoServer] Course Embed Tool```
        -   Url: ```https://[panoptoServer]/Panopto/LTI/LTI.aspx```
        -   LTI version: ```LTI 1.3```
        -   Client ID: ```[Identity Provider > LTI 1.3 Client Identifier]```
        -   Public key type: ```Keyset URL```
        -   Public keyset: ```[Identity Provider > LTI 1.3 Tool JWKS URL]```
        -   Initiate login URL: ```[Identity Provider > LTI 1.3 Tool Login URL]```
        -   Redirection URI(s): ```[Identity Provider > LTI 1.3 Tool Redirection URL]```

9. Save the LTI Tool

## Pre-Requisites
- The [Panopto block for Moodle](https://github.com/Panopto/Moodle-2.0-plugin-for-Panopto) is installed on the Moodle site with at least version 2022122000.
- The target course must be provisioned with Panopto.