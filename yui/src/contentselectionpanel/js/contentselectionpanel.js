// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * YUI module for displaying an LTI launch within a YUI panel.
 *
 * @package mod_panoptocurseembed
 * @copyright  Panopto 2021
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * This panel will create the Panopto LTI embed window and update the content selection buttons on view.php once a session is selected.
 * @method PANOPTOCONTENTSELECTIONFRAME
 */
var PANOPTOCONTENTSELECTIONFRAME = function() {
    PANOPTOCONTENTSELECTIONFRAME.superclass.constructor.apply(this, arguments);
};

Y.extend(PANOPTOCONTENTSELECTIONFRAME, Y.Base, {

    courseid: null,

    /**
     * Init function for the checkboxselection module
     * @property params
     * @type {Object}
     */
    init : function(params) {
        // Check to make sure parameters are initialized
        if ('0' === params.folderviewbtnid ||
            '0' === params.selectvidbtnid ||
            '0' === params.lticimlaunchurl||
            '0' === params.ltilaunchurl ||
             0  === params.courseid ||
             0  === params.height ||
             0  === params.width) {
            return;
        }

        this.courseid = params.courseid;

        var selectvidbtn = Y.one('#' + params.selectvidbtnid),
           folderviewbtn = Y.one('#' + params.folderviewbtnid);
        selectvidbtn.on('click', this.open_panopto_window_callback, this, params.lticimlaunchurl, params.height, params.width);
        folderviewbtn.on('click', this.panopto_folder_view_callback, this, params.ltilaunchurl, 600, 800);

        this._createResourceLinkId = (function (base) {
            return function () {
                return base + '_' + (new Date()).getTime();
            };
        }(params.resourcebase));
    },

    /**
     * Event handler callback for when the panel content is changed
     * @property e
     * @type {Object}
     */
    open_panopto_window_callback: function(e, url, height, width) {
        var panoptoWindow = new M.core.dialogue({
            bodyContent: '<iframe src="' + url + '" width="100%" height="100%"></iframe>',
            headerContent: M.util.get_string('selectvideo', 'panoptocourseembed'),
            width: width,
            height: height,
            draggable: false,
            visible: true,
            zindex: 100,
            modal: true,
            focusOnPreviousTargetAfterHide: true,
            render: true
        });

        document.body.panoptoWindow = panoptoWindow;
        document.body.addEventListener('sessionSelected', this.close_popup_callback.bind(this), false);
    },

    panopto_folder_view_callback: function(e, url, height, width) {// Update the iframe element attributes and sec to point to correct content.
        var newContentSource = new URL(url),
        newIntro = '<p><iframe src="' + newContentSource.toString() + '"' +
                       ' style="width:100%;' +
                               ' height:100%;' +
                               ' min-width:'+ width + 'px;' +
                               ' min-height:' + height + 'px;"' +
                               ' allowfullscreen="true">' +
                    '</iframe><br /></p>';

        Y.one('input[name=intro]').setAttribute('value', newIntro);
        Y.one('#panopto-intro-preview').setContent(newIntro);

        Y.one('#id_select_video').set('value', M.util.get_string('selectvideo', 'panoptocourseembed'));
        // Update button classes.
        Y.one('#id_select_video').removeClass('btn-secondary');
        Y.one('#id_select_video').addClass('btn-primary');
    },

    close_popup_callback: function(closeEvent) {
        // Update the iframe element attributes and sec to point to correct content.
        var newContentSource = new URL(closeEvent.detail.ltiViewerUrl),
            search_params = newContentSource.searchParams,
            newIntro;

        // This will encode the params so decode the json once to make sure it is not double encoded.
        search_params.set('course', this.courseid);
        search_params.set('custom', decodeURI(closeEvent.detail.customData));
        search_params.set('contentUrl', closeEvent.detail.contentUrl);
        search_params.set('resourcelinkid', this._createResourceLinkId());

        // change the search property of the main url
        newContentSource.search = search_params.toString();

        newIntro = '<p><h1>' + closeEvent.detail.title + '</h1>' +
                     '<iframe src="' + newContentSource.toString() + '"' +
                          ' allowfullscreen="true"' +
                          ' width="' + closeEvent.detail.width + '"' +
                          ' height="' + closeEvent.detail.height + '"></iframe><br></p>';

        Y.one('input[name=intro]').setAttribute('value', newIntro);
        Y.one('#panopto-intro-preview').setContent(newIntro);

        Y.one('#id_select_video').set('value', M.util.get_string('replacevideo', 'panoptocourseembed'));
        // Update button classes.
        Y.one('#id_select_video').addClass('btn-secondary');
        Y.one('#id_select_video').removeClass('btn-primary');
        document.body.panoptoWindow.destroy();
    },

},
{
    NAME : 'moodle-mod_panoptocourseembed-contentselectionpanel',
    ATTRS : {
        selectvidbtnid : {
            value: '0'
        },
        folderviewbtnid : {
            value: '0'
        },
        lticimlaunchurl : {
            value: '0'
        },
        ltilaunchurl : {
            value: '0'
        },
        height : {
            value: 0
        },
        width : {
            value: 0
        },
        courseid : {
            value: 0
        },
        resourcebase : {
            value: '0'
        }
    }
});

M.mod_panoptocourseembed = M.mod_panoptocourseembed || {};

/**
 * Entry point for PANOPTOCONTENTSELECTIONFRAME module
 * @param string params additional parameters.
 * @return object the panopto course embed object
 */
M.mod_panoptocourseembed.initcontentselectionpanel = function(params) {
    return new PANOPTOCONTENTSELECTIONFRAME(params);
};