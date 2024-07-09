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
 * AMD module for displaying an LTI launch within a modal.
 *
 * @package mod_panoptocurseembed
 * @copyright  Panopto 2021
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    "jquery",
    "core/notification",
    "core/modal_factory",
    "core/str",
], ($, notification, ModalFactory, str) =>
{
    let courseId = null;
    let isResponsive = false;

    var init = (params) =>
    {
        // Check to make sure parameters are initialized.
        if (   "0" === params.folderviewbtnid
            || "0" === params.selectvidbtnid
            || "0" === params.lticimlaunchurl
            || "0" === params.ltilaunchurl
            || 0 === params.courseid
            || 0 === params.height
            || 0 === params.width
            || 0 === params.isresponsive)
        {
            return;
        }

        courseId = params.courseid;
        isResponsive = params.isresponsive;

        let selectVidBtn = $("#" + params.selectvidbtnid);
        let folderViewBtn = $("#" + params.folderviewbtnid);
        selectVidBtn.on("click", () =>
        {
            open_panopto_window_callback(
                params.lticimlaunchurl,
                params.height,
                params.width
            );
        });
        folderViewBtn.on("click", () =>
        {
            panopto_folder_view_callback(params.ltilaunchurl, 600, 800);
        });

        createResourceLinkId = ((base) => () => base + "_" + new Date().getTime())(params.resourcebase);
    };

    var open_panopto_window_callback = (url, height, width) =>
    {
        // Modal custom size class.
        let modalClass = "mod-panoptocourseembed-modal-custom-size";

        // Ensure unique class names for dynamic styling.
        let modalDialogClass = "mod-panoptocourseembed-modal-dialog-custom";
        let modalContentClass = "mod-panoptocourseembed-modal-content-custom";
        let modalBodyClass = "mod-panoptocourseembed-modal-body-custom";
        let iframeClass = "mod-panoptocourseembed-iframe-custom";

        Promise.all([
            str.get_string("selectvideo", "panoptocourseembed"),
            ModalFactory.create({
                type: ModalFactory.types.DEFAULT,
                body: `<iframe class="${iframeClass}" src="${url}" frameborder="0"></iframe>`,
                large: true,
            }),
        ])
            .then(([selectText, modal]) =>
            {
                modal.setTitle(selectText);
                modal.getRoot().addClass(modalClass);
                modal
                    .getRoot()
                    .find(".modal-dialog")
                    .addClass(modalDialogClass)
                    .css({
                        width: `${width}px`,
                        "max-width": `${width}px`,
                    });
                modal
                    .getRoot()
                    .find(".modal-content")
                    .addClass(modalContentClass)
                    .css({
                        height: `${height}px`,
                        "max-height": `${height}px`,
                    });
                modal
                    .getRoot()
                    .find(".modal-body")
                    .addClass(modalBodyClass);
                modal.show();

                document.body.panoptoWindow = modal;
                document.body.addEventListener(
                    "sessionSelected",
                    close_popup_callback.bind(this),
                    false
                );
            }).catch(notification.exception);
    };

    var panopto_folder_view_callback = (url, height, width) =>
    {
        let newContentSource = new URL(url);
        let newIntro;

        if (isResponsive)
        {
            newIntro = '<p><iframe src="' + newContentSource.toString() + '"' +
                        ' style="width:100%;' +
                                ' height:auto;' +
                                ' aspect-ratio: ' + width + ' / ' + height + ';"' +
                                ' allowfullscreen="true">' +
                        '</iframe><br /></p>';
        }
        else
        {
            newIntro = '<p><iframe src="' + newContentSource.toString() + '"' +
                        ' width="' + width + '"' +
                        ' height="' + height + '"' +
                        ' allowfullscreen="true">' +
                        '</iframe><br /></p>';
        }

        $("input[name=intro]").val(newIntro);
        $("#panopto-intro-preview").html(newIntro);

        str.get_string("selectvideo", "panoptocourseembed")
            .done((selectText) =>
            {
                $("#id_select_video").val(selectText);
            })
            .fail(notification.exception);

        $("#id_select_video")
            .removeClass("btn-secondary")
            .addClass("btn-primary");
    };

    var close_popup_callback = (closeEvent) =>
    {
        let newContentSource = new URL(closeEvent.detail.ltiViewerUrl);
        let searchParams = newContentSource.searchParams;

        searchParams.set("course", courseId);
        searchParams.set("custom", decodeURI(closeEvent.detail.customData));
        searchParams.set("contentUrl", closeEvent.detail.contentUrl);
        searchParams.set("resourcelinkid", createResourceLinkId());

        newContentSource.search = searchParams.toString();

        let newIntro;
        let width = closeEvent.detail.width ?? 800;
        let height = closeEvent.detail.height ?? 600;

        if (isResponsive)
        {
            newIntro = '<p><iframe src="' + newContentSource.toString() + '"' +
                        ' style="width:100%;' +
                                ' height:auto;' +
                                ' aspect-ratio: ' + width + ' / ' + height + ';"' +
                                ' allowfullscreen="true">' +
                        '</iframe><br /></p>';
        }
        else
        {
            newIntro = '<p><iframe src="' + newContentSource.toString() + '"' +
                        ' width="' + width + '"' +
                        ' height="' + height + '"' +
                        ' allowfullscreen="true">' +
                        '</iframe><br /></p>';
        }

        $("input[name=intro]").val(newIntro);
        $("#panopto-intro-preview").html(newIntro);
        str.get_string("replacevideo", "panoptocourseembed")
            .done((replaceText) =>
            {
                $("#id_select_video").val(replaceText);
            })
            .fail(notification.exception);

        $("#id_select_video")
            .addClass("btn-secondary")
            .removeClass("btn-primary");
        document.body.panoptoWindow.destroy();
    };

    return {
        initselectionpanel: init,
    };
});
