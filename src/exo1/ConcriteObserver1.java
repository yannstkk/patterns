package src.exo1;

public class ConcriteObserver1 implements Observer {


    protected String name;

    public ConcriteObserver1(String name) {
        this.name = name;
    }



    @Override
    public void update(String msg) {
        System.out.println(name + " a reçu la notification : " + msg);
    }
    
}
