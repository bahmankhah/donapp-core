/**
 * Turns the SSO user <select> fields on the Donap admin pages into searchable,
 * AJAX-backed dropdowns so admins can find any user (not just the first 100).
 *
 * Progressive enhancement: if Select2/selectWoo is unavailable for any reason,
 * the original native <select> is left completely untouched and keeps working.
 */
(function ($) {
    'use strict';

    $(function () {
        // Prefer WooCommerce's selectWoo, fall back to select2, otherwise bail.
        var plugin = $.fn.selectWoo ? 'selectWoo' : ($.fn.select2 ? 'select2' : null);
        if (!plugin || typeof donapSsoSearch === 'undefined') {
            return;
        }

        $('.donap-sso-search').each(function () {
            var $select = $(this);

            // Each select submits either the WP user ID or the SSO global id.
            var valueField = ($select.data('value-field') === 'sso_id') ? 'sso_id' : 'id';

            $select[plugin]({
                width: 'resolve',
                allowClear: !$select.prop('required'),
                placeholder: donapSsoSearch.placeholder,
                minimumInputLength: parseInt(donapSsoSearch.minChars, 10) || 0,
                language: {
                    searching: function () { return 'در حال جستجو...'; },
                    noResults: function () { return 'کاربری یافت نشد'; },
                    errorLoading: function () { return 'خطا در بارگذاری'; },
                    inputTooShort: function () { return 'برای جستجو تایپ کنید...'; },
                    loadingMore: function () { return 'در حال بارگذاری موارد بیشتر...'; }
                },
                ajax: {
                    url: donapSsoSearch.ajaxUrl,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            action: 'donap_search_sso_users',
                            nonce: donapSsoSearch.nonce,
                            term: params.term || '',
                            page: params.page || 1
                        };
                    },
                    processResults: function (data, params) {
                        params.page = params.page || 1;
                        var items = (data && data.results) ? data.results : [];
                        return {
                            results: items.map(function (u) {
                                return {
                                    id: (valueField === 'sso_id') ? u.sso_id : u.id,
                                    text: u.text
                                };
                            }),
                            pagination: {
                                more: !!(data && data.pagination && data.pagination.more)
                            }
                        };
                    },
                    cache: true
                }
            });
        });
    });
})(jQuery);
