/* global rehEvents */
(function ($) {
    'use strict';

    // =========================================================================
    // Dates meta-box: add / delete dates
    // =========================================================================

    var datesJson = [];

    function initDates() {
        var raw = $('#reh_event_dates_json').val();
        try {
            datesJson = JSON.parse(raw) || [];
        } catch (e) {
            datesJson = [];
        }
    }

    function renderDatesTable() {
        var $tbody = $('#reh-dates-tbody');
        $tbody.empty();

        if (datesJson.length === 0) {
            $tbody.append(
                '<tr id="reh-no-dates-row"><td colspan="8">' +
                reh_i18n('no_dates', 'Noch keine Termine angelegt.') +
                '</td></tr>'
            );
            return;
        }

        $.each(datesJson, function (i, date) {
            var isPast = date.status === 'past';
            var row = $('<tr>')
                .addClass(isPast ? 'reh-date-row-past' : '')
                .attr('data-date-id', date.id);

            row.append('<td>' + escHtml(formatDate(date.date)) + '</td>');
            row.append('<td>' + escHtml(date.time_start || '') + '</td>');
            row.append('<td>' + escHtml(date.time_end || '') + '</td>');
            row.append('<td>' + escHtml(date.capacity !== '' && date.capacity !== undefined ? date.capacity : '') + '</td>');
            row.append('<td>' + escHtml(date.price !== '' && date.price !== undefined ? date.price + ' €' : '') + '</td>');
            row.append('<td>' + escHtml(date.status || 'active') + '</td>');
            row.append('<td>' + escHtml(date.wc_variation_id || '') + '</td>');

            if (!isPast) {
                row.append(
                    '<td><button type="button" class="button button-small reh-delete-date" data-id="' +
                    escAttr(date.id) + '">' +
                    reh_i18n('delete', 'Löschen') +
                    '</button></td>'
                );
            } else {
                row.append('<td>—</td>');
            }

            $tbody.append(row);
        });
    }

    function persistDates() {
        $('#reh_event_dates_json').val(JSON.stringify(datesJson));
    }

    // Add date button
    $(document).on('click', '#reh-add-date', function () {
        var date      = $('#reh_new_date').val();
        var timeStart = $('#reh_new_time_start').val();
        var timeEnd   = $('#reh_new_time_end').val();
        var capacity  = $('#reh_new_capacity').val();
        var price     = $('#reh_new_price').val();

        if (!date || !timeStart || !timeEnd) {
            alert(reh_i18n('fill_required', 'Bitte Datum sowie Von- und Bis-Uhrzeit ausfüllen.'));
            return;
        }

        var newDate = {
            id:             'reh_' + Date.now(),
            date:           date,
            time_start:     timeStart,
            time_end:       timeEnd,
            capacity:       capacity,
            price:          price,
            wc_variation_id: 0,
            status:         'active'
        };

        datesJson.push(newDate);
        persistDates();
        renderDatesTable();

        // Reset fields
        $('#reh_new_date, #reh_new_time_start, #reh_new_time_end, #reh_new_capacity, #reh_new_price').val('');
    });

    // Delete date button
    $(document).on('click', '.reh-delete-date', function () {
        var id = $(this).data('id');
        if (!confirm(reh_i18n('confirm_delete', 'Termin wirklich löschen?'))) {
            return;
        }
        datesJson = datesJson.filter(function (d) { return d.id !== id; });
        persistDates();
        renderDatesTable();
    });

    // =========================================================================
    // Attendee list: check-in
    // =========================================================================

    $(document).on('click', '.reh-checkin-btn', function () {
        var $btn        = $(this);
        var $row        = $btn.closest('tr');
        var orderId     = $btn.data('order-id');
        var variationId = $btn.data('variation-id');
        var index       = $btn.data('index');

        $btn.prop('disabled', true).text(reh_i18n('loading', 'Bitte warten…'));

        $.ajax({
            url:         rehEvents.restUrl + 'checkin',
            method:      'POST',
            beforeSend:  function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', rehEvents.restNonce);
            },
            contentType: 'application/json',
            data:        JSON.stringify({
                order_id:     orderId,
                variation_id: variationId,
                index:        index
            }),
            success: function (response) {
                $row.addClass('checked-in');
                $btn.closest('td').html(
                    '<span class="reh-checked-in-label">✓ ' + escHtml(response.checked_in_at) + '</span>'
                );
            },
            error: function (xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.error
                    ? xhr.responseJSON.error
                    : reh_i18n('checkin_error', 'Check-in fehlgeschlagen.');
                alert(msg);
                $btn.prop('disabled', false).text(reh_i18n('checkin', 'Einchecken'));
            }
        });
    });

    // =========================================================================
    // Helpers
    // =========================================================================

    function reh_i18n(key, fallback) {
        return (rehEvents.i18n && rehEvents.i18n[key]) ? rehEvents.i18n[key] : fallback;
    }

    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str)));
        return div.innerHTML;
    }

    function escAttr(str) {
        return String(str).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function formatDate(str) {
        if (!str) { return ''; }
        var parts = str.split('-');
        if (parts.length !== 3) { return str; }
        return parts[2] + '.' + parts[1] + '.' + parts[0];
    }

    // =========================================================================
    // Init
    // =========================================================================

    $(function () {
        if ($('#reh_event_dates_json').length) {
            initDates();
            renderDatesTable();
        }
    });

}(jQuery));
