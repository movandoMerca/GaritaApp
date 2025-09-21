"use strict";
var KTDatatablesBasicScrollable = function () {
//datatables
    var initTable1 = function () {
        var table = $('#usertb');



        // begin first table
        table.DataTable({
            scrollX:true,
            columnDefs: [{
                targets: -1,
                title: 'Accion',
                orderable: false,
                responsive: true
            }],
        });
    };
    return {
        //main function to initiate the module
        init: function () {
            initTable1();
        },
    };
}();

jQuery(document).ready(function () {
    KTDatatablesBasicScrollable.init();
});
