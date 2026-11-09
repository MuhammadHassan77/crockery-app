/**
 * Select2 Helper Functions
 * Provides reusable utilities for initializing searchable, lazy-loaded selects
 */

/**
 * Initialize a remote (AJAX) Select2 select field
 * @param {string} selector - jQuery selector for the select element
 * @param {string} dataUrl - URL to fetch data from (should return {results: [], pagination: {more: true/false}})
 * @param {object} options - Additional Select2 options
 */
function initRemoteSelect(selector, dataUrl, options = {}) {
  const defaultOptions = {
    theme: 'bootstrap-5',
    language: 'en',
    allowClear: true,
    placeholder: 'Search...',
    minimumInputLength: 1,
    delay: 300,
    cache: false,
    width: '100%',
    ajax: {
      url: dataUrl,
      type: 'GET',
      dataType: 'json',
      data: function(params) {
        return {
          q: params.term || '',
          page: params.page || 1
        };
      },
      processResults: function(data, params) {
        params.page = params.page || 1;
        return {
          results: data.results || [],
          pagination: {
            more: data.pagination && data.pagination.more
          }
        };
      },
      cache: false
    }
  };

  // Merge user options with defaults
  const finalOptions = $.extend(true, defaultOptions, options);

  // Initialize Select2
  $(selector).select2(finalOptions);

  return $(selector);
}

/**
 * Initialize a local (static) Select2 select field
 * @param {string} selector - jQuery selector for the select element
 * @param {object} options - Select2 options
 */
function initLocalSelect(selector, options = {}) {
  const defaultOptions = {
    theme: 'bootstrap-5',
    language: 'en',
    allowClear: true,
    placeholder: 'Select an option...',
    width: '100%'
  };

  const finalOptions = $.extend(true, defaultOptions, options);
  $(selector).select2(finalOptions);

  return $(selector);
}

/**
 * Format Select2 results with custom template
 * @param {object} item - Result item
 * @returns {string} Formatted HTML
 */
function formatSelectResult(item) {
  if (!item.id) return item.text;
  return $('<span>' + $.fn.dataTable.render.escapeHtml()(item.text) + '</span>');
}

/**
 * Format Select2 selection display
 * @param {object} item - Selected item
 * @returns {string} Formatted HTML
 */
function formatSelectSelection(item) {
  return item.text || item.text;
}

/**
 * Show loading state on Select2
 * @param {string} selector - jQuery selector for the select element
 */
function showSelect2Loading(selector) {
  $(selector).prop('disabled', true);
  $(selector).next('.select2-container').addClass('is-loading');
}

/**
 * Hide loading state on Select2
 * @param {string} selector - jQuery selector for the select element
 */
function hideSelect2Loading(selector) {
  $(selector).prop('disabled', false);
  $(selector).next('.select2-container').removeClass('is-loading');
}

/**
 * Destroy Select2 instance
 * @param {string} selector - jQuery selector for the select element
 */
function destroySelect2(selector) {
  $(selector).select2('destroy');
}

/**
 * Get selected value from Select2
 * @param {string} selector - jQuery selector for the select element
 * @returns {string|number} Selected value
 */
function getSelect2Value(selector) {
  return $(selector).val();
}

/**
 * Set selected value in Select2
 * @param {string} selector - jQuery selector for the select element
 * @param {string|number} value - Value to select
 */
function setSelect2Value(selector, value) {
  $(selector).val(value).trigger('change');
}

/**
 * Clear Select2 value
 * @param {string} selector - jQuery selector for the select element
 */
function clearSelect2Value(selector) {
  $(selector).val(null).trigger('change');
}

/**
 * Initialize all Select2 fields that have data-select2 attribute
 * Auto-detects remote vs local based on data-url attribute
 */
document.addEventListener('DOMContentLoaded', function() {
  // Initialize remote selects (with data-url)
  $('[data-select2-remote]').each(function() {
    const url = $(this).data('url');
    const locale = $(this).data('locale') || 'en';
    
    if (url) {
      initRemoteSelect('#' + this.id, url, {
        language: locale,
        placeholder: $(this).attr('placeholder') || 'Search...',
        allowClear: $(this).data('allow-clear') !== false
      });
    }
  });

  // Initialize local selects (with data-select2-local)
  $('[data-select2-local]').each(function() {
    const locale = $(this).data('locale') || 'en';
    
    initLocalSelect('#' + this.id, {
      language: locale,
      placeholder: $(this).attr('placeholder') || 'Select an option...',
      allowClear: $(this).data('allow-clear') !== false
    });
  });
});

// Export for use in modules if needed
if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    initRemoteSelect,
    initLocalSelect,
    formatSelectResult,
    formatSelectSelection,
    showSelect2Loading,
    hideSelect2Loading,
    destroySelect2,
    getSelect2Value,
    setSelect2Value,
    clearSelect2Value
  };
}
