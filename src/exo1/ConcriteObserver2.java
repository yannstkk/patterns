package src.exo1;


public class ConcriteObserver2 implements Observer {




      private String name;

    public ConcriteObserver2(String name) {
        this.name = name;
    }


    @Override
    public void update( String msg) {

    System.out.println(name + " a reçu la notification : " + msg);


    }


    
}
