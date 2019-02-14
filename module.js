M.mod_newsletter = {};

M.mod_newsletter.collapse_subscribe_form = function (Y) {
    var fieldset = Y.one('form #id_subscribe');
    if (fieldset) {
        fieldset.addClass('collapsed');
    }
}

M.mod_newsletter.init_editor = function (Y, stylesheets, selected) {
    if (tinyMCE.activeEditor) {
        var win = tinyMCE.activeEditor.getWin();
        var doc = tinyMCE.activeEditor.getDoc();

        selected = selected != null ? selected : 0;

        YUI({ win: win, doc: doc }).use('node', function(innerY) {
                innerY.all('head link').each(function(node) {
                    node.remove();
                });
                innerY.Node.create('<link />')
                    .set('type', 'text/css')
                    .set('rel', 'stylesheet')
                    .set('href', stylesheets[0])
                    .appendTo(innerY.one('head'));
                innerY.Node.create('<link />')
                    .set('type', 'text/css')
                    .set('rel', 'stylesheet')
                    .set('href', stylesheets[selected])
                    .appendTo(innerY.one('head'));
        });

        function change_stylesheet(e) {
            var select = e.target;

            YUI({ win: win, doc: doc }).use('node', function(innerY) {
                innerY.all('head link').each(function(node) {
                    node.remove();
                });
                innerY.Node.create('<link />')
                    .set('type', 'text/css')
                    .set('rel', 'stylesheet')
                    .set('href', stylesheets[0])
                    .appendTo(innerY.one('head'));
                innerY.Node.create('<link />')
                    .set('type', 'text/css')
                    .set('rel', 'stylesheet')
                    .set('href', stylesheets[select.get('value')])
                    .appendTo(innerY.one('head'));
            });
        }
        Y.one('#id_stylesheetid').on('change', change_stylesheet);
    } else {
        // TODO: this seems like an ugly hack - replace with an event handler if at all possible.
        setTimeout(function(){M.mod_newsletter.init_editor(Y, stylesheets, selected);}, 100);
    }
}
