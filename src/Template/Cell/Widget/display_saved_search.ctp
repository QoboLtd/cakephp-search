<div class='dashboard-widget-saved_search'>
    <?php echo $this->element('Search.search_results', [
        'searchType' => $renderData->type,
        'searchName' => $renderData->name,
        'model'      => $renderData->model,
        'entities'   => $renderData->entities['result'],
        'searchData' => $renderData->entities
    ]);?>
</div>
