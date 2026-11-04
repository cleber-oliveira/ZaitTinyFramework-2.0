/**
 * ZTFramework Frontend - jQuery AJAX Universal Client (ZTF.ajax)
 * 
 * Centraliza e padroniza todas as requisições AJAX via jQuery:
 * - Garante o envio dos cabeçalhos 'X-Requested-With: XMLHttpRequest' e 'Accept: application/json'.
 * - Força o recebimento e parsing de JSON em todas as operações de dados.
 * - Suporta envio de formulários completos (FormData e serialize).
 * - Tratamento unificado de erros (400, 401, 403, 404, 500) evitando quebras de JSON.
 */

(function(window, $) {
    'use strict';

    if (typeof $ === 'undefined') {
        console.error('[ZTFramework] jQuery não foi encontrado. Certifique-se de carregar jquery.min.js antes do zait.ajax.js');
        return;
    }

    // Configuração global do jQuery AJAX para a aplicação
    $.ajaxSetup({
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        dataType: 'json'
    });

    const ZTF = window.ZTF || {};

    ZTF.ajax = {
        /**
         * Executa uma requisição GET retornando JSON.
         */
        get: function(url, data = {}) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: url,
                    type: 'GET',
                    data: data,
                    dataType: 'json'
                })
                .done((response) => resolve(response))
                .fail((jqXHR) => {
                    const errorResponse = jqXHR.responseJSON || {
                        status: false,
                        code: jqXHR.status,
                        error: 'ERR_AJAX_FAIL',
                        message: jqXHR.statusText || 'Falha na comunicação com o servidor.'
                    };
                    reject(errorResponse);
                });
            });
        },

        /**
         * Executa uma requisição POST padrão retornando JSON.
         */
        post: function(url, data = {}) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: data,
                    dataType: 'json'
                })
                .done((response) => resolve(response))
                .fail((jqXHR) => {
                    const errorResponse = jqXHR.responseJSON || {
                        status: false,
                        code: jqXHR.status,
                        error: 'ERR_AJAX_FAIL',
                        message: jqXHR.statusText || 'Falha na comunicação com o servidor.'
                    };
                    reject(errorResponse);
                });
            });
        },

        /**
         * Executa uma requisição com corpo em formato JSON puro.
         */
        postJson: function(url, jsonData = {}) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: url,
                    type: 'POST',
                    contentType: 'application/json; charset=utf-8',
                    data: JSON.stringify(jsonData),
                    dataType: 'json'
                })
                .done((response) => resolve(response))
                .fail((jqXHR) => {
                    const errorResponse = jqXHR.responseJSON || {
                        status: false,
                        code: jqXHR.status,
                        error: 'ERR_AJAX_FAIL',
                        message: jqXHR.statusText || 'Falha na comunicação com o servidor.'
                    };
                    reject(errorResponse);
                });
            });
        },

        /**
         * Submete um formulário HTML via AJAX com suporte a FormData (inclusive arquivos e uploads).
         * 
         * @param {string|HTMLFormElement} formSelector Seletor jQuery ou elemento de form (ex: '#meuForm')
         * @param {string|null} customUrl URL alternativa (se nula, usa o atributo action do form)
         * @param {string|null} customMethod Método alternativo (se nulo, usa o atributo method do form)
         */
        submitForm: function(formSelector, customUrl = null, customMethod = null) {
            const $form = $(formSelector);
            const url = customUrl || $form.attr('action') || window.location.pathname;
            const method = (customMethod || $form.attr('method') || 'POST').toUpperCase();

            const formData = new FormData($form[0]);

            return new Promise((resolve, reject) => {
                $.ajax({
                    url: url,
                    type: method,
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json'
                })
                .done((response) => resolve(response))
                .fail((jqXHR) => {
                    const errorResponse = jqXHR.responseJSON || {
                        status: false,
                        code: jqXHR.status,
                        error: 'ERR_AJAX_FAIL',
                        message: jqXHR.statusText || 'Erro ao submeter formulário.'
                    };
                    reject(errorResponse);
                });
            });
        }
    };

    window.ZTF = ZTF;

})(window, window.jQuery);
