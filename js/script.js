function setMessage(message, style) {
    $('#message').html(message).removeClass('alert-warning').removeClass('alert-success').removeClass('alert-danger');
    $('#message').html(message).addClass('alert-' + style).show();
}

function hideMessage() {
    $('#message').empty().hide().removeClass('alert-*');
}
$(function () {
    $('[data-toggle="tooltip"]').tooltip();
    $('a [data-toggle=tab]').click(function (e) {
        e.preventDefault()
        $(this).tab('show')
    });
    $('.addRow').click(function (event) {
        event.preventDefault();
        var formId = $(this).data('form');
        $('#' + formId + " tbody tr:last").clone().insertAfter('#' + formId + " tbody tr:last");
        $('#' + formId + " tbody tr:last input").val('');
        $('#' + formId + " tbody tr:last textarea").val('');
    });
    $('.addServer').click(function (e) {
        e.preventDefault();
        var formId = $(this).data('form');
        $('#' + formId + " .clonable:last").clone().insertAfter('#' + formId + " tbody .clonable:last");
        $('#' + formId + " .clonable:last input").val('');
        $('#' + formId + " .clonable:last textarea").val('');
    });
    $('body').on('click', '.removeRow', function (e) {
        e.preventDefault();
        if ($(this).parent().parent().parent().children('tr.clonable').length > 1) {
            $(this).parent().parent().remove();
        }
    });
    $('body').on('click', '.removeServer', function (e) {
        e.preventDefault();
        if ($(this).parent().parent().parent().children('.clonable').length > 1) {
            $(this).parent().parent().remove();
        }
    });
    $('.selectpicker').selectpicker();
    $(".time").datetimepicker({format: 'hh:ii:ss', startView: 1, minuteStep: 1});
    $('#formA').submit(function (e) {
        e.preventDefault();
        hideMessage();
        var datas = [];
        var domains = $('#formA input');
        var ips = $('#formA textarea');
        for (var i = 0; i < domains.length; i++) {
            if (domains[i] && ips[i]) {
                datas.push({
                    fqdn: $(domains[i]).val(),
                    ips: $(ips[i]).val()
                });
            }
        }
        console.log(datas);
        $.ajax({
            url: 'a.php',
            data: {
                datas: datas
            },
            method: "POST",
            success: function (datas) {
                if (!datas) {
                    setMessage('Succès !', 'success');
                } else {
                    setMessage(datas, 'warning');
                }
            },
            error: function () {
                setMessage('Impossible d\'éxecuter la requête', 'danger');
            }

        });
    });
    $('#formMX').submit(function (e) {
        e.preventDefault();
        hideMessage();
        var datas = {};
        datas.servers = [];
        var servers = $('#formMX .serversMx');
        datas.domains = $('#formMX textarea').val();
        var priorities = $('#formMX .priorityMx');
        if ($('[name=mailDom]:checked').length) {
            datas.servers.push({
                server: '',
                priority: $('[name=mailDomPriority]').val()
            });
        } else {
            datas.servers.push(false);
        }
        for (var i = 0; i < servers.length; i++) {
            if (servers[i]) {
                datas.servers.push({
                    server: $(servers[i]).val(),
                    priority: $(priorities[i]).val()
                });
            }
        }
        console.log(datas);
        $.ajax({
            url: 'mx.php',
            data: datas,
            method: "POST",
            success: function (datas) {
                if (!datas) {
                    setMessage('Succès !', 'success');
                } else {
                    setMessage(datas, 'warning');
                }
            },
            error: function () {
                setMessage('Impossible d\'éxecuter la requête', 'danger');
            }

        });
    });
    $('#formRedirect').submit(function (e) {
        e.preventDefault();
        hideMessage();
        var datas = [];
        var domains = $('#formRedirect [name=domains]');
        var targets = $('#formRedirect [name=targets]');
        for (var i = 0; i < domains.length; i++) {
            if (domains[i] && targets[i]) {
                datas.push({
                    domain: $(domains[i]).val(),
                    target: $(targets[i]).val()
                });
            }
        }
        console.log(datas);
        $.ajax({
            url: 'redirect.php',
            data: {
                datas: datas
            },
            method: "POST",
            success: function (datas) {
                if (!datas) {
                    setMessage('Succès !', 'success');
                } else {
                    setMessage(datas, 'warning');
                }
            },
            error: function () {
                setMessage('Impossible d\'éxecuter la requête', 'danger');
            }

        });
    });
    $('#formReverse').submit(function (e) {
        e.preventDefault();
        hideMessage();
        console.log($('#formReverse [name=ips]').val());
        $.ajax({
            url: 'reverse.php',
            data: {
                ips: $('#formReverse [name=ips]').val()
            },
            method: "POST",
            success: function (datas) {
                if (!datas) {
                    setMessage('Succès !', 'success');
                } else {
                    setMessage(datas, 'warning');
                }
            },
            error: function () {
                setMessage('Impossible d\'éxecuter la requête', 'danger');
            }

        });
    });
    $('#formClick2Call').submit(function (e) {
        e.preventDefault();
        hideMessage();
        console.log($('#formClick2Call').serialize());
        $.ajax({
            url: 'click2call.php',
            data: $('#formClick2Call').serialize(),
            method: "POST",
            success: function (datas) {
                if (!datas) {
                    setMessage('Succès !', 'success');
                } else {
                    setMessage(datas, 'warning');
                }
            },
            error: function () {
                setMessage('Impossible d\'éxecuter la requête', 'danger');
            }

        });
    });
    $('a[aria-controls="list"]').on('shown.bs.tab', function (e) {
        $.ajax({
            url: 'boxes.php',
            success: function (datas) {
                $('#list').empty().html(datas);
            },
            error: function () {
                setMessage('Impossible d\'afficher la page', 'danger');
            }
        });
    });
    var config_datas = {};
    $('a[aria-controls="boxes"]').on('shown.bs.tab', function (e) {
        $('[aria-controls="list"]').tab('show')
    });
    $('a[aria-controls="config"]').on('shown.bs.tab', function (e) {
        $.ajax({
            url: 'boxes_config.php?list',
            success: function (datas) {
                $('[name="ignoredBoxes"]').empty();
                for (var i = 0; i < datas.length; i++) {
                    $('[name="ignoredBoxes"]').append(
                        $("<option></option>").attr(
                            "value", datas[i].id).text(datas[i].description)
                    );
                }
                $('[name="ignoredBoxes"]').selectpicker('val', JSON.parse(config_datas.ignoredBoxes)).selectpicker('refresh');
            },
            error: function () {
                setMessage('Impossible de charger les données', 'danger');
            }
        });
        $.ajax({
            url: 'boxes_config.php',
            success: function (datas) {
                config_datas = datas;
                $('[name="ignoredBoxes"]').selectpicker('val', JSON.parse(config_datas.ignoredBoxes)).selectpicker('refresh');
                $('[name="email"]').val(config_datas.email);
                $('[name="startNotify"]').val(config_datas.startNotify).datetimepicker('update');
                $('[name="stopNotify"]').val(config_datas.stopNotify).datetimepicker('update');
                $('[name="pingWarn"]').val(config_datas.pingWarn);
                $('[name="pingDanger"]').val(config_datas.pingDanger);
                $('[name="syncDiff"]').val(config_datas.syncDiff);

            },
            error: function () {
                setMessage('Impossible de charger les données', 'danger');
            }
        });
    });
    $('#formBoxesConfig').submit(function (e) {
        e.preventDefault();
        hideMessage();
        console.log($('#formBoxesConfig').serialize());
        $.ajax({
            url: 'boxes_config.php',
            data: $('#formBoxesConfig').serialize(),
            method: "POST",
            success: function (datas) {
                if (datas.success) {
                    setMessage(datas.message, 'success');
                } else {
                    setMessage(datas.message, 'warning');
                }
            },
            error: function () {
                setMessage('Impossible d\'éxecuter la requête', 'danger');
            }

        });
    });
    $('body').on('click', '.panel .close', function(e){
        e.preventDefault();
        var $this = $(this);
        $.ajax({
            url: 'boxes_config.php',
            data: {
                action: 'fixed',
                service: $this.data('service'),
            },
            method: 'GET',
            success: function(datas){
                if (datas.success){
                    $this.parent().parent().parent().remove();
                }
            }
        });
    });
    
})