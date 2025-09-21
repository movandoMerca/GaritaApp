"use strict";

var KTDatatablesBasicScrollable = function () {
    //datatables
    var initTable1 = function () {
        var table = $('#visitreport_today');

        // begin first table
        table.DataTable({
            scrollX: true,
           
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
