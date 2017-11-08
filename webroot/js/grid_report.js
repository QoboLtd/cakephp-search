$(document).ready(function () {
    $('.report-grid').DataTable({
        "dom": 'Bfrtip',
        "buttons": [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        "searching": false,
        "fnFooterCallback": function ( nRow, aaData, iStart, iEnd, aiDisplay ) {
            $.each($(nRow).children(), function (index, elem) {
                if ($(elem).attr('class').indexOf('sum') >= 0) {
                    var iTotalMarket = 0;
                    var aaDataEnd = aaData.length;
                    for (var i = 0; i < aaDataEnd; i++) {
                        iTotalMarket += aaData[i][index] * 1;
                    }

                    elem.innerHTML = iTotalMarket;
                }
            });
        }
    });
});
