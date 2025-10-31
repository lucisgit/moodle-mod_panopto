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
 * @copyright   2020 Tony Butler <a.butler4@lancaster.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Modal from 'core/modal';
import {getString} from 'core/str';
import Templates from 'core/templates';

/**
 * Init function.
 *
 * @param {number} contextId The context id of the Panopto instance.
 * @param {number} panoptoId The instance id of the Panopto instance.
 * @return {Promise} A promise.
 */
export const init = async(contextId, panoptoId) => {
    const templateContext = {
        id: panoptoId,
        idnumber: 'panopto_progress',
        width: 400,
        "class": '',
        value: 0,
        error: 0,
    };
    const modal = await Modal.create({
        title: await getString('preparing', 'mod_panopto'),
        body: await Templates.render('core/progress_bar', templateContext),
        isVerticallyCentered: true,
        removeOnClose: true,
        show: true,
    });
    modal.getRoot().find('.modal-dialog').css({'width': '400px'});

    const progress = document.getElementById('panopto_progress'),
        progressBar = document.getElementById('panopto_progress_bar');
    let intervalId;

    /**
     * Increments the progress bar at an exponentially decreasing rate.
     */
    const incProgress = () => {
        let value = progressBar.getAttribute('value');
        intervalId = setInterval(() => {
            if (progressBar.getAttribute('value') < 100 && value < 99.9) {
                value = value + (1 - value / 100);
                const increment = new CustomEvent('update', {
                    detail: {
                        percent: value,
                    },
                });
                progress.dispatchEvent(increment);
            } else {
                clearInterval(intervalId);
            }
        }, 100);
    };
    incProgress();

    /**
     * Polls a web service method via AJAX for a Panopto auth url, then redirects to it.
     *
     * @param {number} contextId The context id of the Panopto instance.
     * @param {number} panoptoId The instance id of the Panopto instance.
     * @return {Promise} A promise.
     */
    const getAuthUrl = async(contextId, panoptoId) => {
        const request = Ajax.call([{
            methodname: 'mod_panopto_get_auth',
            args: {
                contextid: contextId,
                panoptoid: panoptoId,
            },
        }]);

        try {
            const authUrl = await request[0];
            if (typeof authUrl === 'string') {
                const done = new CustomEvent('update', {
                    detail: {
                        percent: 100,
                    },
                });
                progress.dispatchEvent(done);
                window.location.replace(authUrl);
            }
            // Wait a sec ...
            setTimeout(() => {
                getAuthUrl(contextId, panoptoId);
            }, 1000);
        } catch (exception) {
            const message = await getString(exception.errorcode, 'mod_panopto');
            const failed = new CustomEvent('update', {
                detail: {
                    percent: 0,
                    error: 1,
                    message: message,
                },
            });
            progress.dispatchEvent(failed);
            clearInterval(intervalId);
            const progressStatus = document.getElementById('panopto_progress_status');
            progressStatus.classList.remove('text-truncate');
        }
    };

    // Start polling for an auth url.
    await getAuthUrl(contextId, panoptoId);
};
