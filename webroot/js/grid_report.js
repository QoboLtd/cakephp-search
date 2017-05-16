$(document).ready(function() {
    $('#report').DataTable({
        "dom": 'Bfrtip',
        "buttons": [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ],
        "searching": false,
    });
});
