<?php 

// inside app service provider debugbar

try{
             \Debugbar::disable();//request()->edb=='saad'
       if(request()->has('edb')){
           
            \Debugbar::enable();
            \Cache::put('debugbar', true);
            session()->put('debugbar',true);
        }
          if(\Cache::get('debugbar')){
           \Debugbar::enable();
        }
            
        }catch(\Exception $e){}

inside app.blade.php

 @if(session('debugbar'))
    <script>
        @php
     
      $renderer = \Debugbar::getJavascriptRenderer();
    $renderer->dumpJsAssets();
    @endphp
    </script>
    {!! $renderer->render() !!}
    @endif