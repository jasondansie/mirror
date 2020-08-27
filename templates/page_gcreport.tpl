<section id="content">
    
    <div class="gc-paralax" style=" background-color: #CCC; padding: 3% 0; background-image: url('/images/white-gc-background.png');">

        
        <div class="container col">
  <div id="myCarousel" class="carousel slide" data-ride="carousel" data-interval="7000">
    
    <!-- Wrapper for slides -->
    <div class="carousel-inner">
      <div class="item active d-block w-100">
         <div class="box-header with-border">
                <div class="row">
                    <div class="col-sm-6 pl-1">
                        <h3>Project Results  --  Month Total: € {$total}</h3>
                    </div>
                </div>                  
            </div> 
                        <br> <br>            <!-- /.box-header -->
             <div class="row">     
            <!-- Small boxes (Stat box) -->   
                {foreach from=$allprojectresults item=foo}           
                        <div class="col-lg-3 col-xs-6">
                            <div class="box-header with-border">
                                <h3 class="box-title">{$foo["main_project"]}</h3>               
                            </div>
                            <!-- small box -->
                            <div class="small-box bg-faded">
                              <div class="inner">  

                                <h3>{$foo["Count"]}/{$foo["goal"]}</h3>

                                <p>Results: € {$foo["Count"] * $foo["price"]}</p>
                              </div>
                              <div class="icon">
                                <i class="ion ion-stats-bars"></i>
                              </div>
                              
                            </div>
                        </div>           
                {/foreach}  
            </div>
      </div>
      <div class="item">
           
        <div class="box-header with-border">
                <div class="row">
                    <div class="col-sm-6 pl-1">
                        
                    </div>
                </div>                  
            </div> 
                        <br> <br>            <!-- /.box-header -->
             <div class="row">     
            <!-- Small boxes (Stat box) -->   
                {foreach from=$agentResults item=foo}           
                        <div class="col-lg-3 col-xs-6">
                            <div class="box-header with-border">
                                <h3 class="box-title">{$foo["Agentin_nimi"]}</h3>               
                            </div>
                            <!-- small box -->
                            <div class="small-box bg-faded">
                              <div class="inner">  

                                <h3>{$foo["Count"]}</h3>

                               <p>Results: € {$foo["Count"] * 0}</p>
                              </div>
                              <div class="icon">
                                <i class="ion ion-stats-bars"></i>
                              </div>
                              
                            </div>
                        </div>           
                {/foreach}  
            </div>
      </div>
    </div>
  </div>
</div>

        <!-- LINE CHART -->

        <div class="box-header with-border">
            <h3 class="box-title">Daily Results</h3>

            <div class="box-body">
                <div id="bar-chart" style="height: 300px; width: auto;"></div>
            </div>

        </div>
       
 
    </div>
</section>
 
 