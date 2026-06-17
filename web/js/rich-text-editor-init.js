// Auto-attaches CKEditor (loaded by RichTextAsset) to every
// textarea.rich-text-editor on the page. Toolbar kept deliberately small —
// see RichTextAsset.php for why.
document.addEventListener('DOMContentLoaded', function () {
    if (typeof CKEDITOR === 'undefined') {
        return;
    }

    // Silence the "this CKEditor 4.x is not secure" nag. The 4.25.x LTS that the
    // notice points to is a paid Extended-Support build; here the admin is a
    // single trusted user and all saved HTML is sanitised server-side by
    // app\helpers\RichText::purify(), so the open-source build is acceptable.
    CKEDITOR.config.versionCheck = false;

    var textareas = document.querySelectorAll('textarea.rich-text-editor');
    textareas.forEach(function (el) {
        if (!el.id) {
            return;
        }
        CKEDITOR.replace(el.id, {
            toolbar: [
                { name: 'basicstyles', items: ['Bold', 'Italic'] },
                { name: 'paragraph', items: ['NumberedList', 'BulletedList'] },
                { name: 'links', items: ['Link', 'Unlink'] }
            ],
            removePlugins: 'elementspath,resize',
            height: 180
        });
    });
});
