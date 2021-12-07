$('body').on('click', '.bbprojectcollapse', function () {
    var project_id = $(this).find("span.collapsible").attr('data-project-id');
    var BBcollapsed = $(this).find("span.collapsible");;
    $('#open-' + project_id).hide();
    $('#close-' + project_id).hide();
    $('#wip-' + project_id).show();
    $.ajax('?controller=BoardAjaxController&action=collapseProject&plugin=Bigboard&project_id=' + project_id).done(function (data) {
        if (data.status) {
            $('#close-' + project_id).show();
            $('div[data-project-id="' + project_id + '"]').hide();
        } else {
            $('#open-' + project_id).show();
            $('div[data-project-id="' + project_id + '"]').show();
        }
        $('#wip-' + project_id).hide();
    });
});

$('body').on('click', 'li.collapse_all', function () {
    $('#status_update').html('<span class=alert>update <i class="fa fa-cog fa-spin fa-fw"></i></span>').show();
    $.ajax('?controller=BoardAjaxController&action=collapseAllProjects&plugin=Bigboard').done(function (data) {
        $('div[data-project-id').hide();
        $('.open').hide();
        $('.close').show();
        $('#status_update').html('')
    });
});

$('body').on('click', 'li.expand_all', function () {
    $('#status_update').html('<span class=alert>update <i class="fa fa-cog fa-spin fa-fw"></i></span>').show();
    $.ajax('?controller=BoardAjaxController&action=expandAllProjects&plugin=Bigboard').done(function (data) {
        $('div[data-project-id').show();
        $('.open').show();
        $('.close').hide();
        $('#status_update').html('');
    });
});
