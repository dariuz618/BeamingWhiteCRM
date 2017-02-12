$(function() {

    $('#side-menu').metisMenu();

});

//Loads the correct sidebar on window load,
//collapses the sidebar on window resize.
$(function() {
    $(window).bind("load resize", function() {
        width = (this.window.innerWidth > 0) ? this.window.innerWidth : this.screen.width;
        if (width < 768) {
            $('div.sidebar-collapse').addClass('collapse')
        } else {
            $('div.sidebar-collapse').removeClass('collapse')
        }
    });
    $('.sidebar-hide').click(function() {          
          $('#side-menu').addClass('collapse')
          $('#page-wrapper').css("margin-left", 50);
          $('.navbar-static-side').css("width", 50);
          $('.sidebar-hide').toggle();
          $('.sidebar-show').toggle();
    });
    $('.sidebar-show').click(function() {    
        $('.sidebar-show').toggle();        
        $('.sidebar-hide').toggle();        
        $('#side-menu').removeClass('collapse')  
        $('.navbar-static-side').css("width", 200);  
        $('#page-wrapper').css("margin-left", 200);
    });
    
})
