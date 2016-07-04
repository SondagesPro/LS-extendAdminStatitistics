$(document).ready(function() {
  $("[data-chartid]").each(function()
  {
    var chartId=$(this).data('chartid');
    var dataForCharts=chartjsExtendedData[chartId];
    var graphType=$(this).data('charttype');
    $(this).width($(this).parent().width());
    var ctx   = document.getElementById($(this).attr("id")).getContext("2d");
    console.log(dataForCharts.datasets);
    var chartjsExtended = new Chart(ctx)['StackedBar']({
            labels: dataForCharts.labels,
            datasets: dataForCharts.datasets,
        }
    );
    $("#chartjs-legend-"+chartId).html(chartjsExtended.generateLegend());
  });

});
