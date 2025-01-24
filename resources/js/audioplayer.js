$(document).ready(function() {
    // Select all elements with ids matching the pattern 'element_{n}', where {n} is a number
    $('[class="jp-controls"]').each(function() {
        // This will target each element with the matching class pattern 
        $(this).append('<div class="jp-speed-controls">\
                            <select class="jp-speed" tabindex="0">\
                                <option value="0.5">x0.5</option>\
                                <option value="1" selected="selected">x1</option>\
                                <option value="1.5">x1.5</option>\
                                <option value="2">x2</option>\
                            </select>\
                        </div>');
        $(this).find('.jp-speed').on('change', function (e) {
            const speed = $(this).val();
            $(this).parent().parent().parent().
            parent().parent().parent().find('.jp-player audio').each(function() {
                this.playbackRate = parseFloat(speed);
            });
        });
    });
    $('[id^="element_"]').each(function() {
        // This will target each element with the matching id pattern
        console.log('Found element with id: ' + $(this).attr('id'));

        // You can add your custom logic here to manipulate these elements
        // Example: Adding a class to each element
        $(this).addClass('highlighted');
    });
});
