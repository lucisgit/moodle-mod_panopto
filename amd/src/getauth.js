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
 * Polls a web service to obtain a Panopto auth url and redirect the user,
 * and displays a modal with a progress bar while the user is waiting.
 *
 * See templates:   mod_panopto/progress
 *
 * @module      mod_panopto/getauth
 * @class       getauth
 * @package     mod_panopto
 * @copyright   2020 Tony Butler <a.butler4@lancaster.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(
    [
        'jquery',
        'core/ajax',
        'core/notification',
        'core/str',
        'core/templates',
        'core/modal_factory'
    ],
    function($, ajax, notification, str, templates, ModalFactory) {

        /**
         * Polls a web service method via AJAX for a Panopto auth url, then redirects to it.
         *
         * @param {number} contextId The context id of the Panopto instance.
         * @param {number} panoptoId The instance id of the Panopto instance.
         */
        function getAuthUrl(contextId, panoptoId) {
            var request = ajax.call([{
                methodname: 'mod_panopto_get_auth',
                args: {
                    contextid: contextId,
                    panoptoid: panoptoId
                }
            }]);

            request[0].done(function(authUrl) {
                if (authUrl) {
                    $('#panopto_progress_bar').val(100);
                    window.location.replace(authUrl);
                }
                // Wait a sec ...
                setTimeout(function() {
                    getAuthUrl(contextId, panoptoId);
                }, 1000);
            }).fail(notification.exception);
        }

        return {
            /**
             * Init function.
             *
             * @param {number} contextId The context id of the Panopto instance.
             * @param {number} panoptoId The instance id of the Panopto instance.
             */
            init: function(contextId, panoptoId) {
                // Start polling for an auth url.
                getAuthUrl(contextId, panoptoId);

                var context = {
                    id: 'panopto_progress',
                    width: 350
                },
                    trigger = $('#panopto_info');

                str.get_string('preparing', 'mod_panopto').done(function(title) {
                    ModalFactory.create({
                        title: title,
                        body: templates.render('mod_panopto/progress', context, ''),
                        type: ModalFactory.types.DEFAULT,
                        large: false
                    }, trigger).done(function(modal) {
                        modal.getRoot().find('.modal-dialog').css({'width': '400px', 'margin-top': '20rem'});
                        // Launch modal.
                        trigger.trigger('click');
                    }).fail(notification.exception);
                }).fail(notification.exception);
            }
        };

    }
);
