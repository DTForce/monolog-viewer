angular.module('logFilters', []).filter('jsonTree', function($sce) {
    return function(input) {
        return $sce.trustAsHtml(renderJsonTree(input));
    };
});

function renderJsonTree(input){
    var result = '';
    for (var key in input) {
        if (!input.hasOwnProperty(key)) {
            continue;
        }
        var item = input[key];
        if (typeof item === 'object') {
            var subtree = renderJsonTree(item);
            if (subtree.length > 0) {
                result += '<li class="tree-title">' + key + '</li><ul class="tree-node">' + subtree +'</ul>';
            } else {
                result += '<li class="tree-title">' + key + '</li>';
            }
        } else {
            result += '<li class="tree-leaf">' + key + '<span class="tree-leaf-separator"></span><span class="tree-leaf-value">' + item + '</span></li>';
        }
    }
    return result;
}