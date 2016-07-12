var aChartExtended = new Array();

//~ $(document).ready(function() {
  //~ $("[data-chartid]").each(function()
  //~ {
    //~ var chartId=$(this).data('chartid');
    //~ var dataForCharts=chartjsExtendedData[chartId];
    //~ var graphType=$(this).data('charttype');
    //~ $(this).width($(this).parent().width());
    //~ var ctx   = document.getElementById($(this).attr("id")).getContext("2d");
    //~ console.log(dataForCharts.datasets);
    //~ var chartjsExtended = new Chart(ctx)['StackedBar']({
            //~ labels: dataForCharts.labels,
            //~ datasets: dataForCharts.datasets,
        //~ }
    //~ );
    //~ $("#chartjs-legend-"+chartId).html(chartjsExtended.generateLegend());
  //~ });

//~ });
/**
 * Launch laziloader when document is ready
 */
$(function() {
    $('.lazyloader').lazyload({
        load: function(element) {
            doGraph($(element).data("chartid"));
        }
    });

});

$(document).on('appear','[data-chartid]', function() {
   doGraph($(this).data('chartid'));
});
$(document).on('click','[data-chartaction]', function() {
    var chartAction=$(this).data("chartaction");
    var graphType=$(this).data("graphtype");
    var chartId=$(this).data("chartid");
    doGraph(chartId,graphType);
});
/**
 * do the graph
 * @param $.object chartElement : jquery object : teh complet element
 * @param string|null graphType : force to type of graph (see Chart.js for available
 */
function doGraph(chartId,graphType)
{
    var dataForCharts=chartjsExtendedData[chartId];
    graphType=graphType || dataForCharts.defaultType;
    if (typeof aChartExtended != "undefined") {
        if (typeof aChartExtended[chartId] != "undefined") {
            window.aChartExtended[chartId].destroy();
        }
    }

    // todo : test if graphType is OK
    $("#chartjs-"+chartId).width($("#chartjs-"+chartId).parent().width());
    var ctx   = document.getElementById("chartjs-"+chartId).getContext("2d");
    window.aChartExtended[chartId] = new Chart(ctx)[graphType]({
            labels: dataForCharts.labels,
            datasets: dataForCharts.datasets,
        },
        {
            multiTooltipTemplate: "<%= datasetLabel %> (<%= value %>)",
        }
    );
    $("#chartjs-legend-"+chartId).html(window.aChartExtended[chartId].generateLegend());
}
