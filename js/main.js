$(document).ready(function () {
    // Evento ao alterar um input de imagem
    $('.apt-input').on('change', function () {
        const file = this.files[0];
        const formCampo = $(this).closest('.form-campo');

        const previewBox = formCampo.find('.form-upload-preview');
        const previewImg = formCampo.find('.form-upload-preview-img');
        const previewName = formCampo.find('.form-upload-img-name');
        const uploadLabel = formCampo.find('.form-upload');

        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                previewImg.attr('src', e.target.result);
                previewName.text(file.name);
                previewBox.removeClass('d-none');
                uploadLabel.addClass('d-none');
            };
            reader.readAsDataURL(file);
        }
    });

    // Ao clicar no botão de editar
    $('.form-upload-edit').on('click', function () {
        const formCampo = $(this).closest('.form-campo');
        const inputFile = formCampo.find('.apt-input');
        inputFile.click();
    });

    $('.form-range').on('input', function () {
        $('.form-range-value').text($(this).val());
    });

    $('form').on('submit', function (e) {
        e.preventDefault(); // Impede o envio e o recarregamento da página
    });

    $('.menu-principal').on('click', function (event) {
        const $content = $('.menu-content');

        // Se o clique foi fora do menu-content, fecha
        if (!$content.is(event.target) && $content.has(event.target).length === 0 && !$('.menu-principal').hasClass('active')) {
            $(this).removeClass('active');
        }
    });

    $('.btn-alter').on('click', function () {
        const $btn = $(this);
        const $altBox = $btn.closest('.form-campo').find('.form-alt-box');
        const $addItem = $btn.closest('.main-form').find('.item-add-box');

        var $alt = ($altBox.length > 0) ? $altBox : $addItem;
        var $margin = ($altBox.length > 0) ? '-20px 0 20px' : '-26px 0 26px';
        var $margin2 = ($altBox.length > 0) ? '-20px 0 0px' : '-26px 0 0px';

        if ($btn.hasClass('active')) {
            // Fechar animação
            const height = $alt[0].scrollHeight;
            
            $alt.css({
                height: height + 'px'
            });

            // Força repaint antes de aplicar a nova altura
            $alt[0].offsetHeight;

            $alt.css({
                transition: 'all 0.4s ease-in-out',
                height: '0px',
                margin: $margin
            });

            $btn.removeClass('active');
        } else {
            // Abrir animação
            $alt.css({
                transition: 'none',
                height: 'auto'
            });

            const height = $alt[0].scrollHeight;

            $alt.css({
                height: '0px'
            });

            // Força repaint
            $alt[0].offsetHeight;

            $alt.css({
                transition: 'all 0.4s ease-in-out',
                height: height + 'px',
                margin: $margin2
            });

            $btn.addClass('active');
        }
    });

    $('.apontamento-header').on('click', function () {
        const $collapse = $(this).closest('.apontamento-collapse');
        const $mainTabela = $collapse.find('.main-tabela');

        if ($collapse.hasClass('active')) {
            // Fechar
            const height = $mainTabela[0].scrollHeight;
            $mainTabela.css({
                height: height + 'px'
            });

            // Força o repaint antes da animação
            $mainTabela[0].offsetHeight;

            $mainTabela.css({
                transition: 'all 0.4s ease-in-out',
                height: '0px'
            });

            $collapse.removeClass('active');
        } else {
            // Abrir
            $mainTabela
                .css({
                    transition: 'none',
                    height: 'auto'
                });

            const height = $mainTabela[0].scrollHeight;

            $mainTabela.css({
                height: '0px'
            });

            // Força o repaint antes da animação
            $mainTabela[0].offsetHeight;

            $mainTabela.css({
                transition: 'all 0.4s ease-in-out',
                height: height + 'px'
            });

            $collapse.addClass('active');
        }
    });

    $('.apontamento-collapse').each(function () {
        const $collapse = $(this);
        const $tbody = $collapse.find('tbody');
        const $countSpan = $collapse.find('.apontamento-count');
        const $nenhumTexto = $collapse.find('.nenhum-apontamento');

        // Conta apenas as TRs visíveis que não têm a classe "espaco-tr"
        const totalApontamentos = $tbody.find('tr').not('.espaco-tr').length;

        $countSpan.text(totalApontamentos).show();
        if (totalApontamentos === 0) { $nenhumTexto.show(); }
        else { $nenhumTexto.hide(); }
    });

    $('.silo-dados-btn').on('click', function () {
        const $btn = $(this);
        const $content = $('.silo-dados-content');
        const $icon = $('.silo-dados-btn .btn-icon');

        if ($btn.hasClass('active')) {
            $btn.removeClass('active');
            $icon.removeClass('icon-upload');
            $icon.addClass('icon-close');

            fetch(`../img/icon/icon-upload.svg`)
                .then(response => response.text())
                .then(svg => {
                    $icon.html(svg);
            });
            
            // Fechar animação
            const height = $content[0].scrollHeight;
            
            $content.css({
                height: height + 'px'
            });

            // Força repaint antes de aplicar a nova altura
            $content[0].offsetHeight;

            $content.css({
                transition: 'all 0.4s ease-in-out',
                height: '0px'
            });
        } else {
            $btn.addClass('active');
            $icon.addClass('icon-upload');
            $icon.removeClass('icon-close');

            fetch(`../img/icon/icon-close.svg`)
                .then(response => response.text())
                .then(svg => {
                    $icon.html(svg);
            });

            // Abrir animação
            $content.css({
                transition: 'none',
                height: 'auto'
            });

            const height = $content[0].scrollHeight;

            $content.css({
                height: '0px'
            });

            // Força repaint
            $content[0].offsetHeight;

            $content.css({
                transition: 'all 0.4s ease-in-out',
                height: height + 'px'
            });
        }
    });

    $('.add-btn').on('click', function () {
        const form = $(this).closest('.form-box');
        const totalBlocos = form.length;

        // Clona a form-box-area
        const novoItem = form.clone();
        const novoItemName = form.attr('name');

        // Limpa o select e atualiza o name dinamicamente
        const novoIndex = totalBlocos + 1; // começa de 2
        novoItem.find('select')
            .val('-')
            .attr('name', novoItemName + novoIndex);

        // Restaura opacidade do novo botão
        novoItem.find('.add-btn').css({
            opacity: 0
        });

        // Insere o novo bloco abaixo do atual
        form.after(novoItem);
    });
});