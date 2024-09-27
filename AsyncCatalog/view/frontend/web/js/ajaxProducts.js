define([
    'jquery'
], function ($) {
    'use strict';

    return function (config) {
        $(document).ready(function () {
            $.ajax({
                url: '/asynccatalog/category/ajaxProducts',
                type: 'GET',
                data: {category_id: config.category_id},
                success: function (response) {
                    var productHtml = '';
                    $.each(response, function (index, product) {
                        productHtml += '<div class="product-item">';
                        productHtml += '<img src="' + product.image + '" alt="' + product.name + '"/>';
                        productHtml += '<h2>' + product.name + '</h2>';
                        productHtml += '<span>' + product.price + '</span>';
                        productHtml += '</div>';
                    });
                    $('#category-products').html(productHtml);
                }
            });
        });
    }
});
