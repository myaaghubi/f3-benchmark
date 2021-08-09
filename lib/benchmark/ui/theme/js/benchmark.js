$(window).on('load', function(){
  $("benchmark-panel .main-panel ul li[data-target]").on('click', function(){

    var panel = $(this).data('target');
    var element=$('.'+panel);

    element.toggle();
    setBenchmarkPanelStat(panel, element.is(':visible'));
  });

  function setBenchmarkPanelStat(panel, stat) {
    panel = panel.replace('-', '_');
    stat=stat?1:0;

    $.ajax(benchmarkFatFreeBase+'/benchmark/panel-stat/', {
      type: 'POST',
      data: { panel: panel, stat: stat },
      success: function (data, status, xhr) {
        console.log(data);
      },
      error: function (jqXhr, textStatus, errorMessage) {
        console.log(jqXhr);
      }
    });
  }
});
