
 <script>
  $(function () {
     
    /* ChartJS
     * -------
     * Here we will create a few charts using ChartJS
     */

//--------------
    //- AREA CHART -
    //--------------

    // Get context with jQuery - using jQuery's .get() method.
    var areaChartCanvas = $('#salesChart').get(0).getContext('2d')
    // This will get the first returned node in the jQuery collection.
    var areaChart       = new Chart(areaChartCanvas)

    var areaChartData = {
      labels  : ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October'],
      datasets: [
        
        {
          label               : 'Revenue',
          fillColor           : 'hsl(220, 40%, 45%)',
          strokeColor         : 'hsl(220, 40%, 45%)',
          pointColor          : 'hsl(230, 40%, 45%)',
          pointStrokeColor    : 'hsl(220, 40%, 45%)',
          pointHighlightFill  : '#fff',
          pointHighlightStroke: 'hsl(220, 40%, 45%)',
          data                : [0, 0, 36317.50, 28847.13, 43114.18, 48895.52, 58898.00, 75079.00, 44411.14, 70285.03]
        },
        {
          label               : 'Operating Costs',
          fillColor           : 'hsl(360, 80%, 45%)',
          strokeColor         : 'hsl(370, 80%, 45%)',
          pointColor          : 'hsl(360, 80%, 45%)',
          pointStrokeColor    : '#c1c7d1',
          pointHighlightFill  : '#fff',
          pointHighlightStroke: 'rgba(220,220,220,1)',
          data                : [0, 0, 12318.90, 15045.77, 19228.73, 17778.19, 29464.83, 20336.82, 30652.68, 31112.92]
        },
        {
          label               : 'Bills',
          fillColor           : 'hsl(35, 93%, 50%)',
          strokeColor         : 'hsl(50, 80%, 45%)',
          pointColor          : 'hsl(60, 80%, 45%)',
          pointStrokeColor    : 'hsl(50, 80%, 45%)',
          pointHighlightFill  : 'hsl(35, 93%, 50%)',
          pointHighlightStroke: 'hsl(50, 80%, 45%)',
          data                : [0, 0, 8039.62, 4611.79, 5532.95, 6679.12, 11269.94, 9550.12, 8468.90, 14031.13]
        }
      ]
    }

    var areaChartOptions = {
      //Boolean - If we should show the scale at all
      showScale               : true,
      //Boolean - Whether grid lines are shown across the chart
      scaleShowGridLines      : true,
      //String - Colour of the grid lines
      scaleGridLineColor      : 'rgba(0,0,0,.05)',
      //Number - Width of the grid lines
      scaleGridLineWidth      : 1,
      //Boolean - Whether to show horizontal lines (except X axis)
      scaleShowHorizontalLines: true,
      //Boolean - Whether to show vertical lines (except Y axis)
      scaleShowVerticalLines  : true,
      //Boolean - Whether the line is curved between points
      bezierCurve             : true,
      //Number - Tension of the bezier curve between points
      bezierCurveTension      : 0.3,
      //Boolean - Whether to show a dot for each point
      pointDot                : true,
      //Number - Radius of each point dot in pixels
      pointDotRadius          : 4,
      //Number - Pixel width of point dot stroke
      pointDotStrokeWidth     : 1,
      //Number - amount extra to add to the radius to cater for hit detection outside the drawn point
      pointHitDetectionRadius : 20,
      //Boolean - Whether to show a stroke for datasets
      datasetStroke           : true,
      //Number - Pixel width of dataset stroke
      datasetStrokeWidth      : 2,
      //Boolean - Whether to fill the dataset with a color
      datasetFill             : true,
      //Boolean - whether to maintain the starting aspect ratio or not when responsive, if set to false, will take up entire container
      maintainAspectRatio     : true,
      //Boolean - whether to make the chart responsive to window resizing
      responsive              : true
    }

    //Create the line chart
    areaChart.Line(areaChartData, areaChartOptions)

// Get context with jQuery - using jQuery's .get() method.
    var areaChartCanvas2 = $('#SalaryChart').get(0).getContext('2d')
    // This will get the first returned node in the jQuery collection.
    var areaChart2       = new Chart(areaChartCanvas2)

    var areaChartData2 = {
      labels  : ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November'],
      datasets: [
        
        {
          label               : 'Salaries',
          fillColor           : 'hsl(220, 40%, 45%)',
          strokeColor         : 'hsl(220, 40%, 45%)',
          pointColor          : 'hsl(230, 40%, 45%)',
          pointStrokeColor    : 'hsl(220, 40%, 45%)',
          pointHighlightFill  : '#fff',
          pointHighlightStroke: 'hsl(220, 40%, 45%)',
          data                : [0, 0, 36317.50, 28847.13, 43114.18, 48895.52, 58898.00, 75079.00, 44411.14, 0, 0]
        },
        {
          label               : 'Giving',
          fillColor           : 'hsl(35, 93%, 50%)',
          strokeColor         : 'hsl(50, 80%, 45%)',
          pointColor          : 'hsl(60, 80%, 45%)',
          pointStrokeColor    : 'hsl(50, 80%, 45%)',
          pointHighlightFill  : 'hsl(35, 93%, 50%)',
          pointHighlightStroke: 'hsl(50, 80%, 45%)',
          data                : [0, 0, 4017.60, 110, 5395.24, 200, 8503.92, 1740.09, 10440.55, 14031.14, 2401.15 ]
        },
        {
          label               : 'profit',
          fillColor           : 'hsl(360, 80%, 45%)',
          strokeColor         : 'hsl(370, 80%, 45%)',
          pointColor          : 'hsl(360, 80%, 45%)',
          pointStrokeColor    : '#c1c7d1',
          pointHighlightFill  : '#fff',
          pointHighlightStroke: 'rgba(220,220,220,1)',
          data                : [-7645.35, 2766.65, 7706.88, -9930.76, 7189.64, 11459.90, -9825.31, 21195.63, -13091.00, 14031.14, 2401.15 ]
        },
        
      ]
    }

    var areaChartOptions2 = {
      //Boolean - If we should show the scale at all
      showScale               : true,
      //Boolean - Whether grid lines are shown across the chart
      scaleShowGridLines      : true,
      //String - Colour of the grid lines
      scaleGridLineColor      : 'rgba(0,0,0,.05)',
      //Number - Width of the grid lines
      scaleGridLineWidth      : 1,
      //Boolean - Whether to show horizontal lines (except X axis)
      scaleShowHorizontalLines: true,
      //Boolean - Whether to show vertical lines (except Y axis)
      scaleShowVerticalLines  : true,
      //Boolean - Whether the line is curved between points
      bezierCurve             : true,
      //Number - Tension of the bezier curve between points
      bezierCurveTension      : 0.3,
      //Boolean - Whether to show a dot for each point
      pointDot                : true,
      //Number - Radius of each point dot in pixels
      pointDotRadius          : 4,
      //Number - Pixel width of point dot stroke
      pointDotStrokeWidth     : 1,
      //Number - amount extra to add to the radius to cater for hit detection outside the drawn point
      pointHitDetectionRadius : 20,
      //Boolean - Whether to show a stroke for datasets
      datasetStroke           : true,
      //Number - Pixel width of dataset stroke
      datasetStrokeWidth      : 2,
      //Boolean - Whether to fill the dataset with a color
      datasetFill             : true,
      //Boolean - whether to maintain the starting aspect ratio or not when responsive, if set to false, will take up entire container
      maintainAspectRatio     : true,
      //Boolean - whether to make the chart responsive to window resizing
      responsive              : true
    }

    //Create the line chart
    areaChart2.Line(areaChartData2, areaChartOptions2)
  })
</script>
