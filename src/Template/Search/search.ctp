<section class="content-header">
    <h4>Search</h4>
</section>
<section class="content">
<?php
echo $this->Html->css('AdminLTE./plugins/datatables/dataTables.bootstrap', ['block' => 'css']);
echo $this->Html->script(
    [
        'AdminLTE./plugins/datatables/jquery.dataTables.min',
        'AdminLTE./plugins/datatables/dataTables.bootstrap.min',
        'Search.view-search-result'
    ],
    [
        'block' => 'scriptBotton'
    ]
);

echo $this->element('Search.saved_searches');
echo $this->element('Search.search_filters');
echo $this->element('Search.search_results');
?>
</section>
