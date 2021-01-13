define(['jquery'], function ($) {

    /**
     * Handles the actual closing of nodes
     * @param {Object} $node
     * @param {String} nodeName
     */
    function handleClosingNodes($node, nodeName) {
        $('.list-group-item[data-parent-key="' + nodeName + '"]').each(function () {
            var $this = $(this);
            var key = $this.data('key');
            $this.addClass('localboostnavigationcollapsedparent');
            $('.list-group-item[data-parent-key="' + key + '"]').addClass('localboostnavigationcollapsedchild');
        });
    }

    /**
     * Closes all child nodes of given node name
     * Fixes second level children (e.g Semester modules) not closing when mycourses gets closed
     * @param {String} nodeName
     */
    function closeAllChildNodes(nodeName) {
        var $node = $('.list-group-item[data-key="' + nodeName + '"]');

        $node.click(function () {
            var $this = $(this);

            handleClosingNodes($this, nodeName);
        });
    }

    return {
        closeAllChildNodes: function (nodeName) {
            closeAllChildNodes(nodeName);
        }
    };
});