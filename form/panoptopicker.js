M.form_panoptopicker = {};
M.form_panoptopicker.elements = [];

M.form_panoptopicker.init = function(Y, options) {
    M.form_panoptopicker.elements[options.client_id] = options.element_id;
    options.formcallback = M.form_panoptopicker.callback;
    if (!M.core_filepicker.instances[options.client_id]) {
        M.core_filepicker.init(Y, options);
    }
    Y.on('click', function(e, client_id) {
        e.preventDefault();
        M.core_filepicker.instances[client_id].show();
    }, '#filepicker-button-js-'+options.client_id, null, options.client_id);
};

M.form_panoptopicker.callback = function (params) {
    document.getElementById(M.form_panoptopicker.elements[params.client_id]).value = params.url;
};
