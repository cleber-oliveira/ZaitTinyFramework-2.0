/**
 * ZTFramework Frontend - Upload Base64 Automático (uploadB64)
 * 
 * Intercepta qualquer elemento <input type="file" class="uploadB64"> e:
 * 1. Converte o arquivo selecionado para Base64 instantaneamente via FileReader.
 * 2. Envia para o endpoint de upload (/api/upload) via jQuery AJAX.
 * 3. Valida se o usuário está logado (se não estiver, notifica erro 401).
 * 4. Ao concluir, preenche o campo hidden de destino com o Hash/URL e exibe feedback visual.
 * 
 * EXEMPLO DE USO HTML:
 * <input type="file" 
 *        class="uploadB64" 
 *        data-target="#hashArquivo" 
 *        data-preview="#previewImg" 
 *        data-publico="0"
 *        data-grupos='["admin","gerente"]'>
 * <input type="hidden" id="hashArquivo" name="documento_hash">
 * <img id="previewImg" style="display:none; max-width: 150px;">
 */

(function(window, $) {
    'use strict';

    if (typeof $ === 'undefined') {
        console.error('[uploadB64] jQuery é obrigatório.');
        return;
    }

    // Escuta evento de seleção de arquivo em qualquer input com a classe .uploadB64
    $(document).on('change', 'input[type="file"].uploadB64', function(e) {
        const input = this;
        const file = e.target.files[0];

        if (!file) {
            return;
        }

        const $input = $(input);
        const endpoint = $input.data('endpoint') || 'api/upload';
        const targetSelector = $input.data('target');
        const previewSelector = $input.data('preview');
        const isPublico = $input.data('publico') !== undefined ? parseInt($input.data('publico')) : 0;
        const gruposPermitidos = $input.data('grupos') || [];

        // Remove feedback anterior
        $input.siblings('.uploadb64-feedback').remove();

        // Insere indicador de carregamento
        const $loading = $(`
            <div class="uploadb64-feedback mt-1 text-primary small d-flex align-items-center gap-2">
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                <span>Convertendo para Base64 e enviando <strong>${file.name}</strong>...</span>
            </div>
        `);
        $input.after($loading);
        $input.prop('disabled', true);

        // 1. Converte o arquivo para Base64 usando a API FileReader do navegador
        const reader = new FileReader();

        reader.onload = function(event) {
            const base64Data = event.target.result;

            // Se houver elemento de preview (para imagens)
            if (previewSelector && $(previewSelector).length) {
                $(previewSelector).attr('src', base64Data).show();
            }

            // 2. Dispara o envio via jQuery AJAX para o endpoint de upload
            $.ajax({
                url: endpoint,
                type: 'POST',
                data: {
                    arquivo_base64: base64Data,
                    nome_original: file.name,
                    publico: isPublico,
                    grupos_permitidos: typeof gruposPermitidos === 'string' ? gruposPermitidos : JSON.stringify(gruposPermitidos)
                },
                dataType: 'json'
            })
            .done(function(response) {
                $loading.remove();
                $input.prop('disabled', false);

                if (response.status) {
                    const data = response.data || {};

                    // Preenche o input hidden de destino com o Hash gerado
                    if (targetSelector && $(targetSelector).length) {
                        $(targetSelector).val(data.hash || data.url);
                    }

                    // Guarda atributos de dados no próprio input
                    $input.attr('data-uploaded-hash', data.hash || '');
                    $input.attr('data-uploaded-url', data.url || '');

                    // Feedback visual de sucesso com o nome do arquivo e link
                    const $successBadge = $(`
                        <div class="uploadb64-feedback mt-2 alert alert-success py-2 px-3 small d-flex justify-content-between align-items-center">
                            <span>✅ <strong>${data.nomeOriginal || file.name}</strong> enviado com sucesso!</span>
                            ${data.url ? `<a href="${data.url}" target="_blank" class="btn btn-sm btn-outline-success ms-2">Visualizar</a>` : ''}
                        </div>
                    `);
                    $input.after($successBadge);

                    // Dispara evento customizado de sucesso
                    $input.trigger('upload:success', [data, response]);

                } else {
                    exibirErro(response.message || 'Falha ao processar o upload.');
                }
            })
            .fail(function(jqXHR) {
                $loading.remove();
                $input.prop('disabled', false);

                const err = jqXHR.responseJSON || {};
                let mensagem = err.message || 'Erro de comunicação ao enviar arquivo.';

                if (jqXHR.status === 401) {
                    mensagem = '⚠️ Autenticação necessária! Você precisa estar logado para enviar arquivos.';
                    if ($('#authModal').length) {
                        $('#authModal').modal('show');
                    }
                } else if (jqXHR.status === 403) {
                    mensagem = '🚫 Acesso negado: você não tem permissão para esta ação.';
                }

                exibirErro(mensagem);
                $input.trigger('upload:error', [err, jqXHR]);
            });
        };

        reader.onerror = function() {
            $loading.remove();
            $input.prop('disabled', false);
            exibirErro('Não foi possível ler o arquivo selecionado.');
        };

        function exibirErro(msg) {
            const $errorBadge = $(`
                <div class="uploadb64-feedback mt-2 alert alert-danger py-2 px-3 small">
                    ${msg}
                </div>
            `);
            $input.after($errorBadge);
        }

        // Inicia a leitura do arquivo como DataURL (Base64)
        reader.readAsDataURL(file);
    });

})(window, window.jQuery);
