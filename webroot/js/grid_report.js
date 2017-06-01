$(document).ready(function () {
    var columns = [];
    $('.report-grid').DataTable({
        "dom": 'Bfrtip',
        "buttons": [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        "searching": false,
        "fnFooterCallback": function ( nRow, aaData, iStart, iEnd, aiDisplay ) {
            /*
             * Calculate the total market share for all browsers in this table (ie inc. outside
             * the pagination)
             */

            var nCells = nRow.getElementsByTagName('th');
            $.each($(nRow).children(), function (index, elem) {
                if ($(elem).attr('class').indexOf('sum') >= 0) {
                    var iTotalMarket = 0;
                    var aaDataEnd = aaData.length;
                    for (var i = 0; i < aaDataEnd; i++) {
                        iTotalMarket += aaData[i][index] * 1;
                    }

                    /* Calculate the market share for browsers on this page */
                    var iPageMarket = 0;
                    for (var i = iStart; i < iEnd; i++) {
                        iPageMarket += aaData[ aiDisplay[i] ][index] * 1;
                    }

                    /* Modify the footer row to match what we want */
                    nCells[index].innerHTML = iPageMarket + '(' + iTotalMarket + ' total)';
                }
            });
        }
    });
});
