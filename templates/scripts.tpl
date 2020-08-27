   
<!-- jQuery 3 -->
<script src="../../extranet/bower_components/jquery/dist/jquery.min.js"></script>
<!-- Bootstrap 3.3.7 -->
<script src="../../extranet/bower_components/bootstrap/dist/js/bootstrap.min.js"></script>

<!-- FastClick -->
<script src="../../extranet/bower_components/fastclick/lib/fastclick.js"></script>
<!-- AdminLTE App -->
<script src="../../extranet/dist/js/adminlte.min.js"></script>
<!-- AdminLTE for demo purposes -->
<script src="../../extranet/dist/js/demo.js"></script>

<!-- FLOT CHARTS -->
<script src="../../extranet/bower_components/Flot/jquery.flot.js"></script>
<!-- FLOT RESIZE PLUGIN - allows the chart to redraw when the window is resized -->
<script src="../../extranet/bower_components/Flot/jquery.flot.resize.js"></script>
<!-- FLOT PIE PLUGIN - also used to draw donut charts -->
<script src="../../extranet/bower_components/Flot/jquery.flot.pie.js"></script>
<!-- FLOT CATEGORIES PLUGIN - Used to draw bar charts -->
<script src="../../extranet/bower_components/Flot/jquery.flot.categories.js"></script>

     
 
<script>
  $(function () {
 

    /*
     * BAR CHART
     * ---------
     */

    var bar_data = {
      data : [{$dailychartdata}],
      color: '#3c8dbc'
    }
    $.plot('#bar-chart', [bar_data], {
      grid  : {
        borderWidth: 1,
        borderColor: '#f3f3f3',
        tickColor  : '#f3f3f3'
      },
      series: {
        bars: {
          show    : true,
          barWidth: 0.5,
          align   : 'center'
        }
      },
      xaxis : {
        mode      : 'categories',
        tickLength: 0
      }
    })
    /* END BAR CHART */
  })

  /*
   * Custom Label formatter
   * ----------------------
   */
  
</script>