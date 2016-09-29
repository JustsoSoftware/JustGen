/**
 * Definition of justtexts plugin
 *
 * @copyright  2014-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 */

"use strict";

/* global jQuery, alert */
/*jslint nomen: true */

(function ($) {
    var pageEntry = $("#PageEntry"),
        value = pageEntry.html();

    value = value.replace(/<\/span>/m, '</span><span class="template" contenteditable="true"><%= template %></span>');
    pageEntry.html(value);

    $("#pages").find(".name").after('<span class="template">Template</span>');

    $(".navbar .container").append($('<button id="flush" class="pull-right btn">Flush Cache</button>'));
    $("#flush").on("click", function () {
        $.get("/api/justgen/flushcache")
            .then(function () {
                window.router.refreshPageListView();
            })
            .fail(function (error, message) {
                alert(message + ': ' + error.responseText);
            });
        return false;
    });

}(jQuery));
