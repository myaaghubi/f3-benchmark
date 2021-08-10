$(window).on('load', function(){
  if (benchmarkPanelMain) {
    $('benchmark .main-panel').toggle();
    $('benchmark .main-panel-minimal').toggle();
    if (benchmarkPanelLast!='')
      $('benchmark ul.bencmark-pannels > li.'+benchmarkPanelLast).toggle();
  }

  $("benchmark ul li[data-target]").on('click', function(){
    var panel = $(this).data('target');
    if (panel=='panel-main') {
      $('benchmark .main-panel').toggle();
      $('benchmark .main-panel-minimal').toggle();

      if (benchmarkPanelLast!='')
        $('benchmark ul.bencmark-pannels > li.'+benchmarkPanelLast).toggle();
    } else {
      $('benchmark ul.bencmark-pannels > li:not(:last-child)').hide();
      if (panel==benchmarkPanelLast) {
        $('.'+panel).hide();
        panel = "";
      } else {
        $('.'+panel).show();
      }
      benchmarkPanelLast = panel;
    }
    syncBenchmarkPanelStat();
  });

  function syncBenchmarkPanelStat() {
    var mainPanelVisible = $('benchmark .main-panel').is(':visible')?1:0;
    $.ajax(benchmarkFatFreeBase+'/benchmark/panel-stat/', {
      type: 'POST',
      data: { panel: benchmarkPanelLast, main: mainPanelVisible},
      success: function (data, status, xhr) {
      }
    });
  }
});
