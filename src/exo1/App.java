package src.exo1;



public class App {


    public static void main(String[] args) {

        ConcriteObserver1 o1 = new ConcriteObserver1("yann");
        ConcriteObserver2 o2 = new ConcriteObserver2("paul");

        Subject su = new Subject();

        su.registerObserver(o1);
        su.registerObserver(o2);

        su.notifyObservers();


        NotifStrategy ns = new Email("email");

        su.NotifStrategy(ns);


    }

    
}
