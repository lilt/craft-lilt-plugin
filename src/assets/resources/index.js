var $btngroup = $('<div>', {'class': 'btngroup'});
var $button = $('<button>', {'class': 'btn', 'type': 'button'});
$button.appendTo($btngroup);
$button.html('Translate');
$btngroup.prependTo('#header #action-button');