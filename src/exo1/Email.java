package src.exo1;


public class Email implements NotifStrategy {
        String name;

    public Email(String name) {
        this.name = name;
    }
    
    public void maStrat(){
        System.out.println("on utilise la strat " + name);

    }
}
