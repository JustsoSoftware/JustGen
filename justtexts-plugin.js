/**
 * Definition of justtexts plugin
 *
 * @copyright  2014-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 */

var pageEntry = $("#PageEntry"),
    value = pageEntry.html();

value = value.replace(/<\/span>/m, '</span><span class="template" contenteditable="true"><%= template %></span>');
pageEntry.html(value);

$("#pages").find(".name").after('<span class="template">Template</span>');
