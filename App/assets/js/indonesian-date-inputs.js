<!-- Indonesian Date Input Configuration -->
<script>
// Configure date inputs for Indonesian locale
function configureIndonesianDateInputs() {
    // Configure HTML5 date inputs
    $('input[type="date"]').each(function() {
        const $input = $(this);
        $input.attr('data-format-type', 'date');

        // Add Indonesian placeholder
        if (!$input.attr('placeholder')) {
            $input.attr('placeholder', 'DD/MM/YYYY');
        }
    });

    // Configure datetime-local inputs
    $('input[type="datetime-local"]').each(function() {
        const $input = $(this);
        $input.attr('data-format-type', 'datetime');

        if (!$input.attr('placeholder')) {
            $input.attr('placeholder', 'DD/MM/YYYY HH:MM');
        }
    });

    // Configure time inputs
    $('input[type="time"]').each(function() {
        const $input = $(this);
        $input.attr('data-format-type', 'time');

        if (!$input.attr('placeholder')) {
            $input.attr('placeholder', 'HH:MM');
        }
    });
}

// Configure Bootstrap datepicker for Indonesian locale
function configureIndonesianDatepicker() {
    if (typeof $.fn.datepicker !== 'undefined') {
        // Set Indonesian language for Bootstrap datepicker
        $.fn.datepicker.dates['id'] = {
            days: IndonesianLocale.days.long,
            daysShort: IndonesianLocale.days.short,
            daysMin: IndonesianLocale.days.short.map(d => d.substring(0, 1)),
            months: IndonesianLocale.months.long,
            monthsShort: IndonesianLocale.months.short,
            today: "Hari Ini",
            clear: "Bersihkan",
            format: "dd/mm/yyyy",
            titleFormat: "MM yyyy",
            weekStart: 1
        };

        // Default options for Indonesian datepickers
        $.fn.datepicker.defaults.language = 'id';
        $.fn.datepicker.defaults.format = 'dd/mm/yyyy';
        $.fn.datepicker.defaults.autoclose = true;
        $.fn.datepicker.defaults.todayHighlight = true;
        $.fn.datepicker.defaults.todayBtn = 'linked';
        $.fn.datepicker.defaults.orientation = 'auto';
    }
}

// Format date input values on page load
function formatExistingDateInputs() {
    // Format date inputs
    $('input[type="date"][data-format-type="date"]').each(function() {
        const $input = $(this);
        const value = $input.val();
        if (value && value !== '0000-00-00') {
            // Convert from YYYY-MM-DD to DD/MM/YYYY
            const parts = value.split('-');
            if (parts.length === 3) {
                $input.val(`${parts[2].padStart(2, '0')}/${parts[1].padStart(2, '0')}/${parts[0]}`);
            }
        }
    });

    // Format datetime inputs
    $('input[type="datetime-local"][data-format-type="datetime"]').each(function() {
        const $input = $(this);
        const value = $input.val();
        if (value) {
            // Convert from YYYY-MM-DDTHH:MM to DD/MM/YYYY HH:MM
            const [datePart, timePart] = value.split('T');
            if (datePart && timePart) {
                const dateParts = datePart.split('-');
                const timeFormatted = timePart.substring(0, 5); // HH:MM
                $input.val(`${dateParts[2].padStart(2, '0')}/${dateParts[1].padStart(2, '0')}/${dateParts[0]} ${timeFormatted}`);
            }
        }
    });
}

// Handle date input changes and convert to database format
function handleDateInputChanges() {
    // Handle date input changes
    $(document).on('change', 'input[type="date"][data-format-type="date"]', function() {
        const $input = $(this);
        const value = $input.val();

        if (value) {
            // Convert from DD/MM/YYYY to YYYY-MM-DD for database
            const parts = value.split('/');
            if (parts.length === 3) {
                const dbValue = `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
                $input.attr('data-db-value', dbValue);
            }
        }
    });

    // Handle datetime input changes
    $(document).on('change', 'input[type="datetime-local"][data-format-type="datetime"]', function() {
        const $input = $(this);
        const value = $input.val();

        if (value) {
            // Convert from DD/MM/YYYY HH:MM to YYYY-MM-DDTHH:MM for database
            const [datePart, timePart] = value.split(' ');
            if (datePart && timePart) {
                const dateParts = datePart.split('/');
                const dbValue = `${dateParts[2]}-${dateParts[1].padStart(2, '0')}-${dateParts[0].padStart(2, '0')}T${timePart}:00`;
                $input.attr('data-db-value', dbValue);
            }
        }
    });
}

// Get database value from formatted input
function getDatabaseValue($input) {
    return $input.attr('data-db-value') || $input.val();
}

// Set formatted value to input
function setFormattedValue($input, dbValue) {
    if (!dbValue || dbValue === '0000-00-00') {
        $input.val('');
        return;
    }

    const type = $input.attr('data-format-type');

    if (type === 'date' && dbValue.includes('-')) {
        // Convert YYYY-MM-DD to DD/MM/YYYY
        const parts = dbValue.split('-');
        if (parts.length === 3) {
            $input.val(`${parts[2].padStart(2, '0')}/${parts[1].padStart(2, '0')}/${parts[0]}`);
        }
    } else if (type === 'datetime' && dbValue.includes('T')) {
        // Convert YYYY-MM-DDTHH:MM:SS to DD/MM/YYYY HH:MM
        const [datePart, timePart] = dbValue.split('T');
        if (datePart && timePart) {
            const dateParts = datePart.split('-');
            const timeFormatted = timePart.substring(0, 5); // HH:MM
            $input.val(`${dateParts[2].padStart(2, '0')}/${dateParts[1].padStart(2, '0')}/${dateParts[0]} ${timeFormatted}`);
        }
    } else {
        $input.val(dbValue);
    }

    $input.attr('data-db-value', dbValue);
}

// Initialize Indonesian date/time formatting
$(document).ready(function() {
    configureIndonesianDateInputs();
    configureIndonesianDatepicker();
    formatExistingDateInputs();
    handleDateInputChanges();

    console.log('🇮🇩 Indonesian date inputs configured');
});

// Export functions for global use
window.IndonesianDateInput = {
    getDatabaseValue: getDatabaseValue,
    setFormattedValue: setFormattedValue,
    configureDatepicker: configureIndonesianDatepicker
};
</script>
