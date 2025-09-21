var d = new Date();
var month = d.getMonth() + 1;
var day = d.getDate();
var h = d.getHours();
var m =d.getMinutes();
var ml = d.getSeconds();
var foto = $('#logo').val();
console.log(foto);
var output = (day < 10 ? '0' : '') + day + '/' +
		(month < 10 ? '0' : '') + month + '/' +
		d.getFullYear() + ' ' + h+':'+m+':'+ml;

var tabla = $('#visitreport').DataTable(

    {
        "lengthMenu": [
            [50, 100, 150, -1],
            [50, 100, 150, "All"]
        ],
        "pageLength": 10,
        "order": [
            [1, 'dec'],
            [2, 'asc'],
        ],
        scrollX: true,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.16/i18n/Spanish.json"
        },
        dom: 'QBfrtip',
        buttons: [
            'excel',
            {
                extend: 'pdfHtml5',
                title: 'Reporte de Visitas ' + output,
                orientation: 'landscape',

                customize: function ( doc ) {
                    doc.content.splice( 1, 0, {
                        margin: [ 10, 10, 10, 10 ],
                        alignment: 'center',
                        image: 'data:image/png;base64,'+foto,
                        width: 180,
                        height: 40 
                        } 
                    );
                    //doc.watermark = {text: 'MardySA', color: 'gray', opacity: 0.1 };
                },
                
            },
        ],
       
    },
);