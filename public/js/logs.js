
var foto = $('#logo').val();
console.log(foto);

var d = new Date();
var month = d.getMonth() + 1;
var day = d.getDate();

var h = d.getHours();
var m = d.getMinutes();
var ml = d.getMilliseconds();
var output = (day < 10 ? '0' : '') + day + '/' +
		(month < 10 ? '0' : '') + month + '/' +
		d.getFullYear() +' '+ h+':'+m+':'+ml;

var tabla = $('#logs').DataTable(

    {
        "lengthMenu": [
            [50, 100, 150, -1],
            [50, 100, 150, "All"]
        ],
        "pageLength": 10,
        "order": [
            [1, 'asc'],
            [2, 'asc'],
        ],
        scrollX: true,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/Spanish.json"
        },
        dom: 'QBfrtip',
        buttons: [
            {
                extend: 'excel',
                title: 'Logs ' + output
            }
        ],
       
    },
);

