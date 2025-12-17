public class Sms implements NotifStrategy {



    String name;

    public Sms(String name) {
        this.name = name;
    }
    
    public void maStrat(){
        
        System.out.println("msg envoye a " + name );

    }
    
}
