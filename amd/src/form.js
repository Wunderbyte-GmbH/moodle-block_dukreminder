// File: amd/src/form.js
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Form utilities for dukreminder block.
 *
 * @module     block_dukreminder/form
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {

    /**
     * Insert text at the current cursor position
     * @param {string} text The text to insert
     */
    var insertTextAtCursor = function(text) {
        var sel, range;
        if (window.getSelection) {
            sel = window.getSelection();
            if (sel.getRangeAt && sel.rangeCount) {
                range = sel.getRangeAt(0);
                range.deleteContents();
                range.insertNode(document.createTextNode(text));
            }
        } else if (document.selection && document.selection.createRange) {
            document.selection.createRange().text = text;
        }
    };

    /**
     * Initialize the form module
     */
    var init = function() {
        // Add any initialization code here if needed
        // For example, you could set up event listeners
    };

    // Public API
    return {
        init: init,
        insertTextAtCursor: insertTextAtCursor
    };
});