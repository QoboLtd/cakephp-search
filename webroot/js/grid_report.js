$(document).ready(function () {
    $('.report-grid').DataTable({
        "dom": 'Bfrtip',
        "buttons": [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        "searching": false,
    });
});
