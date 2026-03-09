jQuery(document).ready(function ($) {
    console.log("Form validation and navigation script loaded.");

    let currentFieldsetIndex = 0; // Start with the first fieldset
    const fieldsets = $("fieldset"); // Get all fieldsets
    const progressBar = $(".progress-bar"); // Get the progress bar

    // Hide all fieldsets except the first one
    fieldsets.hide().eq(currentFieldsetIndex).show();

    // Handle "Next" button click
    $(".next").click(function (event) {
    console.log("Next button clicked");
    event.preventDefault(); // Prevent default behavior

    const currentFieldset = fieldsets.eq(currentFieldsetIndex);
    const nextFieldset = currentFieldset.next("fieldset");

    // Validate the current fieldset
    let isValid = true;

    // Check if all rows with radio buttons have at least one checked
    currentFieldset.find(".row.text-center").each(function () {
        const row = $(this);
        const radios = row.find('input[type="radio"]');

        // Check if at least one radio button in this row is selected
        if (radios.length > 0 && radios.filter(":checked").length === 0) {
            isValid = false;
            row.addClass("invalid");
        } else {
            row.removeClass("invalid");
        }
    });

    // Alert and stop if validation fails
    if (!isValid) {
        alert("Please select at least one option for each row before proceeding.");
        return; // Stop here if validation fails
    }

    // Move to the next fieldset if validation passes
    currentFieldset.hide();
    nextFieldset.show();
    currentFieldsetIndex++;
    updateProgressBar();
});


    // Handle "Previous" button click
    $(".previous").click(function (event) {
        console.log("Previous button clicked");
        event.preventDefault(); // Prevent default behavior

        const currentFieldset = fieldsets.eq(currentFieldsetIndex);
        const prevFieldset = currentFieldset.prev("fieldset");

        // Move to the previous fieldset
        currentFieldset.hide();
        prevFieldset.show();
        currentFieldsetIndex--;
        updateProgressBar();
    });

    // Accordion functionality
    $("#assessment-form-container .tab").click(function () {
        const $tab = $(this);

        // Toggle open state
        if ($tab.hasClass("open")) {
            $tab.removeClass("open");
        } else {
            $("#assessment-form-container .tab").removeClass("open");
            $tab.addClass("open");
        }
    });

    // Update progress bar
    function updateProgressBar() {
        const progressPercent = ((currentFieldsetIndex + 1) / fieldsets.length) * 100;
        progressBar.css("width", progressPercent + "%").text(Math.round(progressPercent) + "%");
    }
});
