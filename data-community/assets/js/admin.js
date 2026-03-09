/**
 * Data Community Admin Enhancements
 *
 * - Handles showing/hiding criteria source fields.
 * - Integrates with WordPress Media Uploader for URL fields.
 */
(function($) { // Use jQuery provided by WordPress admin
    'use strict';

    /**
     * Show/Hide criteria input fields based on radio selection.
     */
    function handleCriteriaSourceChange() {
        $('.dc-criterion-group').each(function() {
            var $group = $(this);
            var selectedSource = $group.find('.dc-source-type-selector input[type="radio"]:checked').val();
            var $staticField = $group.find('.dc-static-value-field');
            var $factorField = $group.find('.dc-factor-value-field');
            var $diffField = $group.find('.dc-diff-display-field');

            // Hide all fields first
            $staticField.addClass('dc-hidden-field');
            $factorField.addClass('dc-hidden-field');
            $diffField.addClass('dc-hidden-field');

            // Show the relevant field
            if (selectedSource === 'static') {
                $staticField.removeClass('dc-hidden-field');
            } else if (selectedSource === 'factor') {
                $factorField.removeClass('dc-hidden-field');
            } else if (selectedSource === 'diff') {
                $diffField.removeClass('dc-hidden-field');
            }
        });
    }

/**
 * Media Uploader Integration
 */
function initMediaUploader() {
    // Use event delegation for potentially dynamically added elements, though direct binding is fine here too.
    $('#post').on('click', '.dc-upload-button', function(e) { // Bind to a static parent like #post or document
        e.preventDefault();
        var $button = $(this);
        var targetInputSelector = $button.data('target-input');
        if (!targetInputSelector) {
            console.error('Data Community: Missing data-target-input attribute on upload button.');
            return;
        }
        // Ensure the target input exists
        var $targetInput = $(targetInputSelector);
        if (!$targetInput.length) {
             console.error('Data Community: Target input "' + targetInputSelector + '" not found.');
            return;
        }

        // --- DEBUGGING ---
        console.log('Button clicked for target:', targetInputSelector);
        // --- END DEBUGGING ---


        // Create a new media frame instance each time the button is clicked
        var mediaUploaderInstance = wp.media({
            title: 'Choose JSON File',
            button: {
                text: 'Use this file'
            },
            library: {
                // type: 'application/json' // Optional: remove if not filtering correctly
            },
            multiple: false
        });

        // When a file is selected, grab the URL and set it as the text field's value
        mediaUploaderInstance.on('select', function() {
            var attachment = mediaUploaderInstance.state().get('selection').first().toJSON();

            // --- DEBUGGING ---
            console.log('--- Media selected ---');
            console.log('Attempting to update target:', targetInputSelector); // Log the selector string again
            console.log('Target input jQuery object:', $targetInput); // Log the jQuery object itself
            console.log('Target Input ID:', $targetInput.attr('id')); // Explicitly log the ID attribute
            console.log('Attachment URL:', attachment ? attachment.url : 'No attachment or URL');
            // --- END DEBUGGING ---

            if (attachment && attachment.url) {
                console.log('Updating value for:', $targetInput.attr('id')); // Log the ID before updating
                $targetInput.val(attachment.url).trigger('change');
                console.log('Value updated confirmation for:', $targetInput.attr('id')); // Log after updating
            } else {
                 console.warn('Data Community: Selected attachment has no URL.');
            }
            console.log('--- Select handler finished ---');
        });

        // Open the uploader dialog
        mediaUploaderInstance.open();
    });
}

// --- Document Ready ---
$(document).ready(function() {
    // Ensure other handlers are correctly bound as well
    function handleCriteriaSourceChange() {
       // ... (keep your existing function here) ...
       $('.dc-criterion-group').each(function() {
            var $group = $(this);
            var selectedSource = $group.find('.dc-source-type-selector input[type="radio"]:checked').val();
            var $staticField = $group.find('.dc-static-value-field');
            var $factorField = $group.find('.dc-factor-value-field');
            var $diffField = $group.find('.dc-diff-display-field');

            $staticField.addClass('dc-hidden-field');
            $factorField.addClass('dc-hidden-field');
            $diffField.addClass('dc-hidden-field');

            if (selectedSource === 'static') {
                $staticField.removeClass('dc-hidden-field');
            } else if (selectedSource === 'factor') {
                $factorField.removeClass('dc-hidden-field');
            } else if (selectedSource === 'diff') {
                $diffField.removeClass('dc-hidden-field');
            }
        });
    }

    // Initial check on page load
    handleCriteriaSourceChange();
    // Bind change handler to radio buttons
    $('#post').on('change', '.dc-source-type-selector input[type="radio"]', handleCriteriaSourceChange);

    // Media Uploader
    initMediaUploader(); // Call the function to set up the handler
});

})(jQuery);