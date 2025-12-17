import java.util.ArrayList;
import java.util.List;

public class Subject {

    private List<Observer> ob = new ArrayList<>();
    
    void registerObserver(Observer o){
        ob.add(o);
    }
    
    void removeObserver(Observer o){
        ob.remove(o);
    }
    
    void notifyObservers(){
        for(Observer o: ob){
            o.update("hola");
        }
        
    }

    void NotifStrategy(NotifStrategy nt){
        nt.maStrat();

    }
}
